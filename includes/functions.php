<?php
// includes/functions.php - Cleaned version without duplicates

/**
 * Get site setting by key
 */
function get_site_setting($key, $default = '') {
    global $conn;
    static $settings_cache = [];
    
    if (isset($settings_cache[$key])) {
        return $settings_cache[$key];
    }
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $settings_cache[$key] = $row['setting_value'];
            return $row['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Settings error: " . $e->getMessage());
    }
    
    $settings_cache[$key] = $default;
    return $default;
}

/**
 * Get all site settings with caching
 */
function get_site_settings() {
    global $conn;
    static $settings_cache = null;
    
    if ($settings_cache !== null) {
        return $settings_cache;
    }
    
    $settings = [];
    try {
        $sql = "SELECT setting_key, setting_value FROM site_settings";
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        error_log("Error loading site settings: " . $e->getMessage());
    }
    
    $settings_cache = $settings;
    return $settings;
}

/**
 * Format currency with symbol
 */
function format_currency($amount, $currency_symbol = '₹') {
    return $currency_symbol . number_format($amount, 2);
}

/**
 * Format price with currency
 */
function format_price($amount, $include_currency = true) {
    $settings = get_site_settings();
    $currency_symbol = $settings['currency_symbol'] ?? '$';
    
    if ($include_currency) {
        return $currency_symbol . number_format($amount, 2);
    }
    
    return number_format($amount, 2);
}

/**
 * Get categories with caching and error handling
 */
function get_categories($status = 'active', $use_cache = true) {
    global $conn;
    static $categories_cache = [];
    
    $cache_key = "categories_$status";
    
    if ($use_cache && isset($categories_cache[$cache_key])) {
        return $categories_cache[$cache_key];
    }
    
    try {
        $sql = "SELECT * FROM categories";
        if ($status) {
            $sql .= " WHERE status = ?";
        }
        $sql .= " ORDER BY sort_order, name";
        
        if ($status) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        $categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        if ($use_cache) {
            $categories_cache[$cache_key] = $categories;
        }
        
        return $categories;
        
    } catch (Exception $e) {
        error_log("Error getting categories: " . $e->getMessage());
        // Return default categories if database error
        return [
            ['id' => 1, 'name' => 'Fiction', 'slug' => 'fiction'],
            ['id' => 2, 'name' => 'Non-Fiction', 'slug' => 'non-fiction'],
            ['id' => 3, 'name' => 'Romance', 'slug' => 'romance'],
            ['id' => 4, 'name' => 'Mystery', 'slug' => 'mystery'],
            ['id' => 5, 'name' => 'Science Fiction', 'slug' => 'science-fiction'],
            ['id' => 6, 'name' => 'Biography', 'slug' => 'biography']
        ];
    }
}

/**
 * Get category by ID
 */
function get_category_by_id($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Error getting category: " . $e->getMessage());
        return null;
    }
}

/**
 * Get products with filters
 */
function get_products($filters = []) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM products WHERE (status = 'active' OR status IS NULL)";
        $params = [];
        $types = "";
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category_id'];
            $types .= "i";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR author LIKE ? OR description LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $types .= "sss";
        }
        
        if (!empty($filters['featured'])) {
            $sql .= " AND featured = 1";
        }
        
        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_low':
                $sql .= " ORDER BY price ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY price DESC";
                break;
            case 'rating':
                $sql .= " ORDER BY rating DESC";
                break;
            case 'popular':
            case 'bestseller':
                $sql .= " ORDER BY sales_count DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY created_at DESC";
                break;
        }
        
        // Limit
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = $filters['limit'];
            $types .= "i";
        }
        
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($sql);
        }
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product by ID
 */
function get_product_by_id($id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return null;
    }
}

/**
 * Cart Functions
 */
function get_cart_count() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

function get_cart_total() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

function get_cart_items() {
    return $_SESSION['cart'] ?? [];
}

function add_to_cart($product_id, $quantity = 1) {
    global $conn;
    
    try {
        // Get product details
        $stmt = $conn->prepare("SELECT id, title, price, stock_quantity FROM products WHERE id = ? AND (status = 'active' OR status IS NULL)");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $product = $result->fetch_assoc();
        
        // Check stock
        if ($product['stock_quantity'] < $quantity) {
            return false;
        }
        
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Add or update cart item
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'title' => $product['title'],
                'price' => $product['price'],
                'quantity' => $quantity
            ];
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Add to cart error: " . $e->getMessage());
        return false;
    }
}

function remove_from_cart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

function update_cart_quantity($product_id, $quantity) {
    if ($quantity <= 0) {
        return remove_from_cart($product_id);
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        return true;
    }
    
    return false;
}

function clear_cart() {
    $_SESSION['cart'] = [];
}

/**
 * Utility Functions
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect('/bookshelf/login.php');
    }
}

function has_permission($required_role = 'user') {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? 'customer';
    
    switch ($required_role) {
        case 'admin':
            return $user_role === 'admin';
        case 'user':
            return in_array($user_role, ['admin', 'customer']);
        default:
            return false;
    }
}

function get_user_by_id($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

function generate_order_number() {
    return 'ORD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
}

/**
 * SEO and Display Functions
 */
function get_page_title($default = 'Bookory - Your Online Bookstore') {
    global $page_title_override;
    
    if (isset($page_title_override)) {
        return $page_title_override;
    }
    
    $settings = get_site_settings();
    $site_name = $settings['site_name'] ?? 'Bookory';
    
    // Auto-generate titles based on current page
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    switch ($current_page) {
        case 'index':
            return $site_name . ' - Your Online Bookstore';
        case 'shop':
            $title = 'Shop Books - ' . $site_name;
            if (isset($_GET['category'])) {
                $category = get_category_by_id($_GET['category']);
                if ($category) {
                    $title = $category['name'] . ' Books - ' . $site_name;
                }
            }
            if (isset($_GET['search'])) {
                $title = 'Search Results for "' . htmlspecialchars($_GET['search']) . '" - ' . $site_name;
            }
            return $title;
        case 'product-details':
            if (isset($_GET['id'])) {
                $product = get_product_by_id($_GET['id']);
                if ($product) {
                    return $product['title'] . ' by ' . $product['author'] . ' - ' . $site_name;
                }
            }
            return 'Product Details - ' . $site_name;
        case 'cart':
            return 'Shopping Cart - ' . $site_name;
        case 'checkout':
            return 'Checkout - ' . $site_name;
        case 'my-account':
            return 'My Account - ' . $site_name;
        case 'contact':
            return 'Contact Us - ' . $site_name;
        case 'about':
            return 'About Us - ' . $site_name;
        default:
            return ucfirst(str_replace('-', ' ', $current_page)) . ' - ' . $site_name;
    }
}

function generate_meta_tags($title = null, $description = null, $keywords = null) {
    $settings = get_site_settings();
    
    $title = $title ?: get_page_title();
    $description = $description ?: ($settings['site_description'] ?? 'Your favorite online bookstore');
    $keywords = $keywords ?: 'books, online bookstore, buy books, fiction, non-fiction';
    
    echo '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    echo '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    
    // Open Graph tags
    echo '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:url" content="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">' . "\n";
}

function render_breadcrumb($items) {
    echo '<nav aria-label="breadcrumb" class="mb-4">';
    echo '<ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $is_last = ($index === count($items) - 1);
        
        if ($is_last) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['title']) . '</li>';
        } else {
            echo '<li class="breadcrumb-item">';
            if (isset($item['url'])) {
                echo '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a>';
            } else {
                echo htmlspecialchars($item['title']);
            }
            echo '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

function generate_breadcrumbs($current_page, $extra_items = []) {
    $breadcrumbs = [
        ['title' => 'Home', 'url' => '/bookshelf/', 'icon' => 'bi bi-house']
    ];
    
    // Add extra items from parameters
    foreach ($extra_items as $item) {
        $breadcrumbs[] = $item;
    }
    
    // Add current page
    $breadcrumbs[] = ['title' => $current_page, 'url' => null, 'icon' => null];
    
    echo '<nav aria-label="breadcrumb" class="mb-4">';
    echo '<ol class="breadcrumb bg-light p-3 rounded">';
    
    foreach ($breadcrumbs as $index => $crumb) {
        $is_last = ($index === count($breadcrumbs) - 1);
        
        echo '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '">';
        
        if (!$is_last && $crumb['url']) {
            echo '<a href="' . htmlspecialchars($crumb['url']) . '" class="text-decoration-none">';
            if ($crumb['icon']) {
                echo '<i class="' . $crumb['icon'] . ' me-1"></i>';
            }
            echo htmlspecialchars($crumb['title']);
            echo '</a>';
        } else {
            if ($crumb['icon']) {
                echo '<i class="' . $crumb['icon'] . ' me-1"></i>';
            }
            echo htmlspecialchars($crumb['title']);
        }
        
        echo '</li>';
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Book Card Rendering Function
 */
function render_book_card($book, $show_quick_view = true) {
    $image_url = !empty($book['image_url']) ? htmlspecialchars($book['image_url']) : 
                 (!empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : 
                 'https://placehold.co/300x450/e2e8f0/64748b?text=No+Image');
    $title = htmlspecialchars($book['title']);
    $author = htmlspecialchars($book['author'] ?? 'Unknown Author');
    $price = number_format($book['price'], 2);
    $original_price = isset($book['original_price']) && $book['original_price'] > $book['price'] ? 
                     number_format($book['original_price'], 2) : null;
    $rating = $book['rating'] ?? 0;
    $reviews_count = $book['reviews_count'] ?? 0;
    $stock_quantity = $book['stock_quantity'] ?? 0;
    $sales_count = $book['sales_count'] ?? 0;
    
    // Calculate discount percentage
    $discount_percentage = 0;
    if ($original_price && $book['original_price'] > $book['price']) {
        $discount_percentage = round((($book['original_price'] - $book['price']) / $book['original_price']) * 100);
    }
    
    // Stock status
    $stock_status = $stock_quantity > 0 ? 'in_stock' : 'out_of_stock';
    $stock_class = $stock_status === 'in_stock' ? 'text-success' : 'text-danger';
    $stock_text = $stock_status === 'in_stock' ? 'In Stock' : 'Out of Stock';
    
    // Low stock warning
    $low_stock = $stock_quantity > 0 && $stock_quantity <= 5;
    
    // Generate star rating HTML
    $stars_html = '';
    if ($rating > 0) {
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars_html .= '<i class="bi bi-star-fill text-warning"></i>';
            } elseif ($i - 0.5 <= $rating) {
                $stars_html .= '<i class="bi bi-star-half text-warning"></i>';
            } else {
                $stars_html .= '<i class="bi bi-star text-muted"></i>';
            }
        }
    }
    
    // Category badge
    $category_name = $book['category_name'] ?? '';
    
    // Featured badge
    $is_featured = isset($book['featured']) && $book['featured'] == 1;
    
    // Bestseller badge (high sales count)
    $is_bestseller = $sales_count > 50; // Adjust threshold as needed
    
    echo '<div class="col">';
    echo '<div class="card book-card h-100 position-relative">';
    
    // Discount badge
    if ($discount_percentage > 0) {
        echo '<div class="position-absolute top-0 start-0 m-2" style="z-index: 2;">';
        echo '<span class="badge bg-danger">-' . $discount_percentage . '%</span>';
        echo '</div>';
    }
    
    // Product image
    echo '<div class="card-img-wrapper overflow-hidden">';
    echo '<img src="' . $image_url . '" class="card-img-top" alt="' . $title . '" style="height: 250px; object-fit: cover;" loading="lazy">';
    echo '</div>';
    
    echo '<div class="card-body d-flex flex-column">';
    
    // Title and Author
    echo '<h5 class="card-title mb-1" style="font-size: 1rem; font-weight: 600; line-height: 1.4; height: 2.8rem; overflow: hidden;">' . $title . '</h5>';
    echo '<p class="text-muted mb-2" style="font-size: 0.875rem;">by ' . $author . '</p>';
    
    // Rating
    if ($rating > 0) {
        echo '<div class="rating mb-2 d-flex align-items-center gap-1">';
        echo '<div class="stars" style="font-size: 0.875rem;">' . $stars_html . '</div>';
        if ($reviews_count > 0) {
            echo '<small class="text-muted">(' . $reviews_count . ')</small>';
        }
        echo '</div>';
    }
    
    // Price
    echo '<div class="price-section mb-3">';
    echo '<span class="price fw-bold text-primary" style="font-size: 1.125rem;">$' . $price . '</span>';
    if ($original_price) {
        echo '<span class="original-price text-muted text-decoration-line-through ms-2" style="font-size: 0.875rem;">$' . $original_price . '</span>';
    }
    echo '</div>';
    
    // Stock status
    echo '<div class="stock-status mb-3">';
    echo '<small class="' . $stock_class . '">';
    echo '<i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>';
    echo $stock_text;
    echo '</small>';
    echo '</div>';
    
    // Action buttons
    echo '<div class="mt-auto">';
    echo '<a href="/bookshelf/product-details.php?id=' . $book['id'] . '" class="btn btn-outline-primary btn-sm w-100 mb-2">';
    echo '<i class="bi bi-info-circle me-1"></i>View Details';
    echo '</a>';
    
    if ($stock_status === 'in_stock') {
        echo '<button class="btn btn-primary btn-sm w-100 add-to-cart-btn" onclick="addToCart(' . $book['id'] . ')">';
        echo '<i class="bi bi-cart-plus me-1"></i>Add to Cart';
        echo '</button>';
    } else {
        echo '<button class="btn btn-outline-secondary btn-sm w-100" disabled>';
        echo '<i class="bi bi-x-circle me-1"></i>Out of Stock';
        echo '</button>';
    }
    echo '</div>';
    
    echo '</div>'; // card-body
    echo '</div>'; // card
    echo '</div>'; // col
}
?>