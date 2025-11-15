<?php
// Fixed header.php - Complete working version without warnings
session_start();
require_once __DIR__ . '/db_connect.php';

// Helper function to safely handle null values in htmlspecialchars
function safe_html($value, $default = '') {
    if (is_null($value) || $value === '') {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

// Simple cart functions
if (!function_exists('get_cart_count')) {
    function get_cart_count() {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return 0;
        }
        
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += isset($item['quantity']) ? $item['quantity'] : 0;
        }
        return $count;
    }
}

if (!function_exists('get_cart_total')) {
    function get_cart_total() {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return 0;
        }
        
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['price']) ? $item['price'] : 0;
            $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
            $total += $price * $quantity;
        }
        return $total;
    }
}

// Initialize default settings first
$settings = [
    'site_name' => 'Bookory',
    'site_description' => 'Your favorite online bookstore',
    'site_phone' => '+1 840-841-2569',
    'site_email' => 'info@bookory.com',
    'sticky_header' => '1',
    'sticky_header_offset' => '100',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'accent_color' => '#ffc107',
    'text_color' => '#2c3e50',
    'bg_color' => '#ffffff',
    'success_color' => '#10b981',
    'danger_color' => '#ef4444',
    'info_color' => '#3b82f6',
    'warning_color' => '#f59e0b',
    'dark_bg' => '#1a202c',
    'dark_surface' => '#2d3748',
    'dark_text' => '#e2e8f0',
    'site_logo' => '',
    'site_favicon' => '',
    'theme_mode' => 'light',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'linkedin_url' => '',
    'youtube_url' => '',
    'pinterest_url' => ''
];

// Get site settings from database and merge with defaults
try {
    $sql_settings = "SELECT setting_key, setting_value FROM site_settings";
    $result_settings = $conn->query($sql_settings);
    if ($result_settings) {
        while ($row = $result_settings->fetch_assoc()) {
            if (!empty($row['setting_value'])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
} catch (Exception $e) {
    // Use default settings if table doesn't exist
    error_log("Settings error: " . $e->getMessage());
}

// Get categories safely
$categories = [];
try {
    $sql_categories = "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 10";
    $result_categories = $conn->query($sql_categories);
    if ($result_categories) {
        $categories = $result_categories->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Default categories if table doesn't exist
    $categories = [
        ['id' => 1, 'name' => 'Fiction', 'slug' => 'fiction'],
        ['id' => 2, 'name' => 'Non-Fiction', 'slug' => 'non-fiction'],
        ['id' => 3, 'name' => 'Romance', 'slug' => 'romance'],
        ['id' => 4, 'name' => 'Mystery', 'slug' => 'mystery'],
        ['id' => 5, 'name' => 'Science Fiction', 'slug' => 'science-fiction'],
        ['id' => 6, 'name' => 'Biography', 'slug' => 'biography'],
        ['id' => 7, 'name' => 'Children', 'slug' => 'children'],
        ['id' => 8, 'name' => 'Business', 'slug' => 'business']
    ];
}

// Current page detection
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_url = $_SERVER['REQUEST_URI'];

function is_active($url) {
    global $current_url;
    return strpos($current_url, $url) !== false;
}

// Page title
$page_title = $settings['site_name'];
if (isset($page_title_override)) {
    $page_title = $page_title_override . ' - ' . $page_title;
}

// Generate complementary colors
function adjustBrightness($hex, $percent) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) != 6) return $hex;
    
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
}

// RGB conversion function
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) != 6) return '0,0,0';
    return implode(',', array_map('hexdec', str_split($hex, 2)));
}

// Set color variables
$primary_color = $settings['primary_color'];
$secondary_color = $settings['secondary_color'];
$accent_color = $settings['accent_color'];
$text_color = $settings['text_color'];
$bg_color = $settings['bg_color'];
$success_color = $settings['success_color'];
$danger_color = $settings['danger_color'];
$info_color = $settings['info_color'];
$warning_color = $settings['warning_color'];
$dark_bg = $settings['dark_bg'];
$dark_surface = $settings['dark_surface'];
$dark_text = $settings['dark_text'];

// Generate hover colors
$primary_hover = adjustBrightness($primary_color, -10);
$secondary_hover = adjustBrightness($secondary_color, -10);
$accent_hover = adjustBrightness($accent_color, -10);

// Site branding
$site_logo = $settings['site_logo'] ?? '';
$site_favicon = $settings['site_favicon'] ?? '';
$theme_mode = $settings['theme_mode'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safe_html($page_title); ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo safe_html($settings['site_description']); ?>">
    <meta name="keywords" content="books, online bookstore, buy books, fiction, non-fiction, bestsellers">
    <meta name="author" content="<?php echo safe_html($settings['site_name']); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo safe_html($page_title); ?>">
    <meta property="og:description" content="<?php echo safe_html($settings['site_description']); ?>">
    <meta property="og:type" content="website">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/bookshelf/public/css/style.css">
    <link rel="stylesheet" href="/bookshelf/public/css/responsive-fixes.css">

    <!-- Favicon -->
    <?php if(!empty($site_favicon)): ?>
    <link rel="icon" type="image/x-icon" href="/bookshelf/public/images/<?php echo safe_html($site_favicon); ?>">
    <?php else: ?>
    <link rel="icon" type="image/x-icon" href="/bookshelf/public/images/favicon.ico">
    <?php endif; ?>

    <!-- Dynamic Theme Colors -->
    <style id="dynamic-theme-colors">
    :root {
        /* Primary Colors from Admin Settings */
        --primary-color: <?php echo $primary_color; ?>;
        --primary-hover: <?php echo $primary_hover; ?>;
        --primary-light: <?php echo adjustBrightness($primary_color, 40); ?>;
        --primary-dark: <?php echo adjustBrightness($primary_color, -20); ?>;
        
        --secondary-color: <?php echo $secondary_color; ?>;
        --secondary-hover: <?php echo $secondary_hover; ?>;
        --secondary-light: <?php echo adjustBrightness($secondary_color, 40); ?>;
        --secondary-dark: <?php echo adjustBrightness($secondary_color, -20); ?>;
        
        --accent-color: <?php echo $accent_color; ?>;
        --accent-hover: <?php echo $accent_hover; ?>;
        --accent-light: <?php echo adjustBrightness($accent_color, 40); ?>;
        --accent-dark: <?php echo adjustBrightness($accent_color, -20); ?>;
        
        /* Text Colors */
        --text-primary: <?php echo $text_color; ?>;
        --text-secondary: <?php echo adjustBrightness($text_color, 40); ?>;
        --text-muted: <?php echo adjustBrightness($text_color, 60); ?>;
        
        /* Background Colors */
        --bg-color: <?php echo $bg_color; ?>;
        --bg-light: <?php echo adjustBrightness($bg_color, -5); ?>;
        --bg-dark: <?php echo adjustBrightness($bg_color, -10); ?>;
        
        /* Status Colors */
        --success-color: <?php echo $success_color; ?>;
        --danger-color: <?php echo $danger_color; ?>;
        --warning-color: <?php echo $warning_color; ?>;
        --info-color: <?php echo $info_color; ?>;
        
        /* Dark Mode Colors */
        --dark-bg: <?php echo $dark_bg; ?>;
        --dark-surface: <?php echo $dark_surface; ?>;
        --dark-text: <?php echo $dark_text; ?>;
        
        /* RGB Values */
        --primary-color-rgb: <?php echo hexToRgb($primary_color); ?>;
        --secondary-color-rgb: <?php echo hexToRgb($secondary_color); ?>;
        --accent-color-rgb: <?php echo hexToRgb($accent_color); ?>;
        --text-primary-rgb: <?php echo hexToRgb($text_color); ?>;
        --success-color-rgb: <?php echo hexToRgb($success_color); ?>;
        
        /* UI Variables */
        --border-radius: 12px;
        --box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        --transition: all 0.3s ease;
        
        /* Dynamic Gradients */
        --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        --gradient-secondary: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        --gradient-accent: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
    }

    /* Apply dynamic colors to elements */
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        color: var(--text-primary);
        background-color: var(--bg-color);
    }

    .text-primary { color: var(--primary-color) !important; }
    .bg-primary { background-color: var(--primary-color) !important; }
    .btn-primary {
        background: var(--gradient-primary);
        border: none;
        color: white;
    }
    .btn-primary:hover {
        background: var(--gradient-secondary);
        transform: translateY(-2px);
    }

    .categories-btn {
        background: var(--gradient-primary);
    }

    .categories-btn:hover {
        background: var(--gradient-secondary);
    }

    a {
        color: var(--primary-color);
    }

    a:hover {
        color: var(--primary-hover);
    }

    <?php if($theme_mode == 'dark'): ?>
    body {
        background-color: var(--dark-bg) !important;
        color: var(--dark-text) !important;
    }
    <?php endif; ?>
    
    
    /* Sticky Navigation Only - Not the entire header */
.site-header {
    position: relative;
    z-index: 1030;
}

/* Only make the navigation sticky, not the entire header */
.main-nav {
    position: relative;
    transition: all 0.3s ease;
    background: white;
}

.main-nav.sticky {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1040;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { 
        transform: translateY(-100%);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

/* Add padding to body when nav is sticky */
body.nav-sticky {
    padding-top: 80px; /* Adjust based on your nav height */
}

/* Hide top bar and main header when scrolling */
.site-header.scrolled .header-top,
.site-header.scrolled .header-main {
    display: none;
}

/* Optional: Make the sticky nav more compact */
.main-nav.sticky .categories-btn {
    padding: 0.5rem 1rem;
}

.main-nav.sticky .nav-link {
    padding: 0.5rem 0.75rem;
}

.main-nav.sticky .support-info {
    font-size: 0.875rem;
}

/* Optional: Add logo to sticky nav */
.sticky-logo {
    display: none;
    align-items: center;
    margin-right: 1rem;
}

.main-nav.sticky .sticky-logo {
    display: flex;
}

/* Adjust responsive behavior */
@media (max-width: 991.98px) {
    body.nav-sticky {
        padding-top: 60px;
    }
    
    .main-nav.sticky .categories-btn {
        font-size: 0.875rem;
        padding: 0.4rem 0.8rem;
    }
}

/* Smooth transition for all elements */
.main-nav * {
    transition: all 0.3s ease;
}
    </style>
</head>

<body data-sticky="<?php echo safe_html($settings['sticky_header'], '1'); ?>" 
      data-offset="<?php echo safe_html($settings['sticky_header_offset'], '100'); ?>">

<!-- Skip Navigation -->
<a href="#main-content" class="skip-link visually-hidden-focusable">Skip to main content</a>

<header class="site-header bg-white shadow-sm">
    <!-- Top Bar -->
    <div class="header-top bg-light py-2 d-none d-lg-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="top-links">
                        <a href="/bookshelf/about.php" class="text-muted text-decoration-none me-3 small">
                            <i class="bi bi-info-circle me-1"></i>About Us
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/bookshelf/my-account.php" class="text-muted text-decoration-none me-3 small">
                                <i class="bi bi-person me-1"></i>My Account
                            </a>
                            <a href="/bookshelf/wishlist.php" class="text-muted text-decoration-none small">
                                <i class="bi bi-heart me-1"></i>Wishlist
                            </a>
                        <?php else: ?>
                            <a href="/bookshelf/login.php" class="text-muted text-decoration-none me-3 small">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                            </a>
                            <a href="/bookshelf/register.php" class="text-muted text-decoration-none small">
                                <i class="bi bi-person-plus me-1"></i>Register
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="top-info">
                        <span class="text-muted small me-3">
                            <i class="bi bi-telephone me-1"></i>
                            <?php echo safe_html($settings['site_phone']); ?>
                        </span>
                        <span class="text-muted small">
                            <i class="bi bi-envelope me-1"></i>
                            <?php echo safe_html($settings['site_email']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="header-main py-3">
        <div class="container">
            <div class="row align-items-center">
                <!-- Logo -->
                <div class="col-lg-3 col-md-4 col-6">
                    <a class="navbar-brand d-flex align-items-center text-decoration-none" href="/bookshelf/">
                        <?php if(!empty($settings['site_logo'])): ?>
                            <img src="/bookshelf/public/images/<?php echo safe_html($settings['site_logo']); ?>" 
                                 alt="<?php echo safe_html($settings['site_name']); ?>" 
                                 class="logo-img me-2" style="max-height: 40px;">
                        <?php else: ?>
                            <i class="bi bi-book text-primary fs-2 me-2"></i>
                        <?php endif; ?>
                        <span class="logo-text fs-2 fw-bold text-primary">
                            <?php echo safe_html($settings['site_name']); ?>
                        </span>
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="col-lg-5 col-md-8 col-12 order-3 order-md-2 mt-3 mt-md-0">
                    <form class="search-form" action="/bookshelf/shop.php" method="GET">
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search books, authors, ISBN..."
                                   value="<?php echo isset($_GET['search']) ? safe_html($_GET['search']) : ''; ?>">
                            <select name="category" class="form-select" style="max-width: 140px;">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo safe_html($cat['id']); ?>" 
                                            <?php echo (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo safe_html($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary px-4" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Header Actions -->
                <div class="col-lg-4 col-md-12 order-2 order-md-3 text-end">
                    <div class="header-actions d-flex align-items-center justify-content-end gap-3">
                        
                        <!-- User Account -->
                        <div class="dropdown">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="#" class="text-dark text-decoration-none dropdown-toggle" 
                                   data-bs-toggle="dropdown">
                                    <i class="bi bi-person fs-5"></i>
                                    <span class="d-none d-md-inline ms-1">
                                        <?php echo safe_html($_SESSION['user_name'] ?? 'Account'); ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/bookshelf/my-account.php">
                                        <i class="bi bi-person me-2"></i>My Account
                                    </a></li>
                                    <li><a class="dropdown-item" href="/bookshelf/orders.php">
                                        <i class="bi bi-bag me-2"></i>My Orders
                                    </a></li>
                                    <li><a class="dropdown-item" href="/bookshelf/wishlist.php">
                                        <i class="bi bi-heart me-2"></i>Wishlist
                                    </a></li>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="/bookshelf/admin/">
                                            <i class="bi bi-gear me-2"></i>Admin Panel
                                        </a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/bookshelf/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a></li>
                                </ul>
                            <?php else: ?>
                                <a href="/bookshelf/login.php" class="text-dark text-decoration-none">
                                    <i class="bi bi-box-arrow-in-right fs-5"></i>
                                    <span class="d-none d-md-inline ms-1">Login</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Wishlist -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/bookshelf/wishlist.php" class="text-dark text-decoration-none position-relative">
                                <i class="bi bi-heart fs-5"></i>
                                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle wishlist-count" 
                                      style="font-size: 0.6em; display: none;">0</span>
                            </a>
                        <?php endif; ?>

                        <!-- Shopping Cart -->
                        <a class="text-dark text-decoration-none position-relative" href="/bookshelf/cart.php">
                            <i class="bi bi-bag fs-5"></i>
                            <span class="badge bg-primary rounded-pill position-absolute top-0 start-100 translate-middle cart-count" 
                                  style="font-size: 0.6em;">
                                <?php echo get_cart_count(); ?>
                            </span>
                            <span class="d-none d-lg-inline ms-2">
                                <small class="d-block">Cart</small>
                                <small class="d-block fw-bold">$<?php echo number_format(get_cart_total(), 2); ?></small>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="main-nav navbar navbar-expand-lg bg-light border-top" id="mainNav">
    <div class="container">
        <div class="row w-100 align-items-center">
            
            
            <div class="sticky-logo">
                <a href="/bookshelf/" class="text-decoration-none d-flex align-items-center">
                    <?php if(!empty($settings['site_logo'])): ?>
                        <img src="/bookshelf/public/images/<?php echo safe_html($settings['site_logo']); ?>" 
                             alt="<?php echo safe_html($settings['site_name']); ?>" 
                             style="height: 30px;">
                    <?php else: ?>
                        <i class="bi bi-book text-primary fs-4 me-1"></i>
                        <span class="fw-bold text-primary"><?php echo safe_html($settings['site_name']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            
                
                <!-- Categories Dropdown -->
                <div class="col-lg-3">
                    <div class="dropdown">
                        <button class="btn btn-primary w-100 d-flex justify-content-between align-items-center dropdown-toggle categories-btn" 
                                type="button" data-bs-toggle="dropdown">
                            <span><i class="bi bi-grid me-2"></i>Browse Categories</span>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a class="dropdown-item" href="/bookshelf/shop.php?category=<?php echo safe_html($category['id']); ?>">
                                        <i class="bi bi-book me-2"></i><?php echo safe_html($category['name']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item fw-bold" href="/bookshelf/shop.php">
                                    <i class="bi bi-grid me-2"></i>View All Categories
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Mobile Toggle -->
                <div class="col-6 d-lg-none">
                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#navbarNav">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                </div>

                <!-- Main Menu -->
                <div class="col-lg-6">
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav justify-content-center w-100">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'index' ? 'active fw-bold' : ''; ?>" 
                                   href="/bookshelf/">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'shop' ? 'active fw-bold' : ''; ?>" 
                                   href="/bookshelf/shop.php">Shop</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    Categories
                                </a>
                                <ul class="dropdown-menu">
                                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                        <li>
                                            <a class="dropdown-item" href="/bookshelf/shop.php?category=<?php echo safe_html($category['id']); ?>">
                                                <i class="bi bi-book me-2"></i><?php echo safe_html($category['name']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/bookshelf/shop.php">View All</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/bookshelf/shop.php?featured=1">Featured</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/bookshelf/shop.php?sort=bestseller">Bestsellers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'contact' ? 'active fw-bold' : ''; ?>" 
                                   href="/bookshelf/contact.php">Contact</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Support Info -->
                <div class="col-lg-3 d-none d-lg-flex justify-content-end">
                    <div class="support-info d-flex align-items-center">
                        <i class="bi bi-headset fs-4 me-2 text-primary"></i>
                        <div>
                            <div class="text-danger fw-bold small">
                                <?php echo safe_html($settings['site_phone']); ?>
                            </div>
                            <div class="text-muted small">24/7 Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Main Content -->
<main id="main-content" class="main-content">

<!-- Additional CSS and JavaScript remain the same as your original -->
<style>
/* Your existing styles here */
.site-header {
    position: relative;
    z-index: 1030;
    transition: all 0.3s ease;
}
.site-header.sticky {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    animation: slideDown 0.3s ease;
}

.site-header.sticky .header-top {
    display: none;
}

.site-header.sticky .header-main {
    padding: 0.75rem 0;
}

@keyframes slideDown {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

/* Logo */
.logo-text {
    transition: font-size 0.3s ease;
}

.site-header.sticky .logo-text {
    font-size: 1.75rem !important;
}

/* Search Form */
.search-form .form-control {
    border-radius: 25px 0 0 25px;
    border-right: none;
}

.search-form .form-select {
    border-radius: 0;
    border-left: none;
    border-right: none;
}

.search-form .btn {
    border-radius: 0 25px 25px 0;
    border-left: none;
}

/* Categories Button */
.categories-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.categories-btn:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Navigation Links */
.nav-link {
    color: #495057 !important;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: #667eea !important;
    background: rgba(102, 126, 234, 0.1);
}

.nav-link.active {
    color: #667eea !important;
    font-weight: 700;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 30px;
    height: 3px;
    background: #667eea;
    border-radius: 2px;
}

/* Header Actions */
.header-actions a {
    color: #495057;
    transition: all 0.3s ease;
    padding: 0.5rem;
    border-radius: 8px;
}

.header-actions a:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

/* Dropdown Menus */
.dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 0.5rem 0;
}

.dropdown-item {
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: #667eea;
    color: white;
}

/* Sticky Body Padding */
body.header-sticky {
    padding-top: 200px;
    transition: padding-top 0.3s ease;
}

/* Responsive */
@media (max-width: 991.98px) {
    .header-main .row > div:nth-child(2) {
        order: 3;
        margin-top: 1rem;
    }
    
    body.header-sticky {
        padding-top: 160px;
    }
}

@media (max-width: 767.98px) {
    .header-actions span {
        display: none !important;
    }
    
    body.header-sticky {
        padding-top: 140px;
    }
}

/* Focus States */
.nav-link:focus,
.btn:focus,
.form-control:focus,
.form-select:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .site-header {
        display: none;
    }
    
    body.header-sticky {
        padding-top: 0;
    }
}

</style>

<script>

document.addEventListener('DOMContentLoaded', function() {
    // Sticky Header
    const stickyEnabled = document.body.dataset.sticky === '1';
    const stickyOffset = parseInt(document.body.dataset.offset) || 100;
    
    if (stickyEnabled) {
        const header = document.querySelector('.site-header');
        let isSticky = false;
        
        function handleSticky() {
            const scrollTop = window.pageYOffset;
            
            if (scrollTop > stickyOffset && !isSticky) {
                header.classList.add('sticky');
                document.body.classList.add('header-sticky');
                isSticky = true;
            } else if (scrollTop <= stickyOffset && isSticky) {
                header.classList.remove('sticky');
                document.body.classList.remove('header-sticky');
                isSticky = false;
            }
        }
        
        let ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    handleSticky();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
    
    // Mobile Menu Toggle
    const toggler = document.querySelector('.navbar-toggler');
    if (toggler) {
        toggler.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            if (icon) {
                icon.className = isExpanded ? 'bi bi-list fs-3' : 'bi bi-x fs-3';
            }
        });
    }
    
    // Search Enhancement
    const searchInput = document.querySelector('.search-form input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    }
    
    // Cart Count Update Function
    window.updateCartCount = function() {
        const cartBadge = document.querySelector('.cart-count');
        if (cartBadge) {
            fetch('/bookshelf/ajax/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = data.count > 0 ? 'inline' : 'none';
                    }
                })
                .catch(error => console.log('Cart update error:', error));
        }
    };
    
    // Initialize cart count
    updateCartCount();
    
    // Listen for cart updates
    document.addEventListener('cartUpdated', updateCartCount);
});

// Global Add to Cart Function
window.addToCart = function(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    return fetch('/bookshelf/ajax/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            
            // Show success message if SweetAlert is available
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Success!',
                    text: 'Item added to cart',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        } else {
            throw new Error(data.message || 'Failed to add item');
        }
        return data;
    })
    .catch(error => {
        console.error('Add to cart error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error!',
                text: error.message,
                icon: 'error',
                toast: true,
                position: 'top-end'
            });
        }
    });
};

document.addEventListener('DOMContentLoaded', function() {
    const themeMode = '<?php echo $theme_mode; ?>';
    
    if (themeMode === 'auto') {
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.body.classList.add('dark-mode');
        }
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (e.matches) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        });
    } else if (themeMode === 'dark') {
        document.body.classList.add('dark-mode');
    }
});

// Function to update theme colors dynamically (for live preview in admin)
function updateThemeColors(colors) {
    const root = document.documentElement;
    if (colors.primary) root.style.setProperty('--primary-color', colors.primary);
    if (colors.secondary) root.style.setProperty('--secondary-color', colors.secondary);
    if (colors.accent) root.style.setProperty('--accent-color', colors.accent);
    
    // Update gradients
    root.style.setProperty('--gradient-primary', 
        `linear-gradient(135deg, ${colors.primary || 'var(--primary-color)'} 0%, ${colors.secondary || 'var(--secondary-color)'} 100%)`);
}

// Social Media Icons in Footer (if needed)
const socialLinks = {
    facebook: '<?php echo $settings['facebook_url'] ?? ''; ?>',
    twitter: '<?php echo $settings['twitter_url'] ?? ''; ?>',
    instagram: '<?php echo $settings['instagram_url'] ?? ''; ?>',
    linkedin: '<?php echo $settings['linkedin_url'] ?? ''; ?>',
    youtube: '<?php echo $settings['youtube_url'] ?? ''; ?>',
    pinterest: '<?php echo $settings['pinterest_url'] ?? ''; ?>'
};


document.addEventListener('DOMContentLoaded', function() {
    // Sticky header functionality
    const stickyEnabled = document.body.dataset.sticky === '1';
    const stickyOffset = parseInt(document.body.dataset.offset) || 100;
    
    if (stickyEnabled) {
        const header = document.querySelector('.site-header');
        let isSticky = false;
        
        function handleSticky() {
            const scrollTop = window.pageYOffset;
            
            if (scrollTop > stickyOffset && !isSticky) {
                header.classList.add('sticky');
                document.body.classList.add('header-sticky');
                isSticky = true;
            } else if (scrollTop <= stickyOffset && isSticky) {
                header.classList.remove('sticky');
                document.body.classList.remove('header-sticky');
                isSticky = false;
            }
        }
        
        window.addEventListener('scroll', function() {
            requestAnimationFrame(handleSticky);
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Sticky Navigation Bar Only (not entire header)
    const stickyEnabled = document.body.dataset.sticky === '1';
    const stickyOffset = parseInt(document.body.dataset.offset) || 100;
    
    if (stickyEnabled) {
        const nav = document.querySelector('.main-nav');
        const header = document.querySelector('.site-header');
        let isSticky = false;
        
        function handleStickyNav() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > stickyOffset && !isSticky) {
                // Make only nav sticky
                nav.classList.add('sticky');
                header.classList.add('scrolled');
                document.body.classList.add('nav-sticky');
                isSticky = true;
            } else if (scrollTop <= stickyOffset && isSticky) {
                // Remove sticky
                nav.classList.remove('sticky');
                header.classList.remove('scrolled');
                document.body.classList.remove('nav-sticky');
                isSticky = false;
            }
        }
        
        // Use requestAnimationFrame for smooth scrolling
        let ticking = false;
        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    handleStickyNav();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        // Initial check
        handleStickyNav();
    }
});
</script>