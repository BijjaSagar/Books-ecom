<?php
// Enhanced index.php - Modern Bookstore Design
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();

// Include header with enhanced error checking
$header_file = 'includes/header.php';
if (!file_exists($header_file)) {
    die("Error: Header file not found at: " . $header_file);
}

try {
    include $header_file;
} catch (Exception $e) {
    die("Error loading header: " . $e->getMessage());
}

if (!isset($conn)) {
    die("Error: Database connection not established");
}

// Initialize variables
$result_favorites = null;
$result_featured = null;
$result_categories = null;
$result_sliders = null;
$settings = [];

// Enhanced data fetching with better error handling
try {
    // Check if products table exists
    $table_check = "SHOW TABLES LIKE 'products'";
    $table_result = $conn->query($table_check);
    
    if ($table_result && $table_result->num_rows > 0) {
        // First try to get explicitly featured products
        $sql_featured = "SELECT *, 
                        COALESCE(rating, 4.2) as rating,
                        COALESCE(sales_count, 0) as sales_count,
                        featured as is_featured
                        FROM products 
                        WHERE featured = 1 AND (status = 'active' OR status IS NULL)
                        ORDER BY created_at DESC 
                        LIMIT 8";
        $result_featured = $conn->query($sql_featured);
        
        // Debug: If no featured books, get all active products as featured
        if (!$result_featured || $result_featured->num_rows == 0) {
            $sql_featured_alt = "SELECT *, 
                               COALESCE(rating, 4.2) as rating,
                               COALESCE(sales_count, 0) as sales_count,
                               1 as is_featured
                               FROM products 
                               WHERE (status = 'active' OR status IS NULL)
                               ORDER BY created_at DESC 
                               LIMIT 8";
            $result_featured = $conn->query($sql_featured_alt);
        }
        
        // Debug info - remove in production
        if ($result_featured) {
            error_log("Featured books query returned: " . $result_featured->num_rows . " rows");
        } else {
            error_log("Featured books query failed: " . $conn->error);
        }
        
        // Get bestsellers
        $sql_bestsellers = "SELECT *, 
                           COALESCE(rating, 4.2) as rating,
                           COALESCE(sales_count, 0) as sales_count
                           FROM products 
                           WHERE status = 'active' 
                           ORDER BY sales_count DESC, rating DESC 
                           LIMIT 8";
        $result_bestsellers = $conn->query($sql_bestsellers);
    }

    // Get categories
    $cat_check = "SHOW TABLES LIKE 'categories'";
    $cat_result = $conn->query($cat_check);
    
    if ($cat_result && $cat_result->num_rows > 0) {
        $sql_categories = "SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8";
        $result_categories = $conn->query($sql_categories);
    }

    // Get site settings
    $settings_check = "SHOW TABLES LIKE 'site_settings'";
    $settings_result = $conn->query($settings_check);
    
    if ($settings_result && $settings_result->num_rows > 0) {
        $sql_settings = "SELECT setting_key, setting_value FROM site_settings";
        $result_settings = $conn->query($sql_settings);
        if ($result_settings) {
            while ($row = $result_settings->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

} catch (Exception $e) {
    error_log("Database error in index.php: " . $e->getMessage());
}

// Enhanced book card rendering function
function render_enhanced_book_card($book, $show_badges = true, $show_rating = true) {
    if (!$book) return;
    
    $image_url = !empty($book['image_url']) ? htmlspecialchars($book['image_url']) : 
                 (!empty($book['cover_image']) ? htmlspecialchars($book['cover_image']) : 
                 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=400&fit=crop&crop=center');
    $title = htmlspecialchars($book['title'] ?? 'Untitled');
    $author = htmlspecialchars($book['author'] ?? 'Unknown Author');
    $price = isset($book['price']) ? number_format($book['price'], 2) : '0.00';
    $original_price = (isset($book['original_price']) && $book['original_price'] > ($book['price'] ?? 0)) ? 
                     number_format($book['original_price'], 2) : null;
    $rating = $book['rating'] ?? 4.2;
    $reviews_count = $book['reviews_count'] ?? rand(15, 250);
    $is_featured = isset($book['is_featured']) && $book['is_featured'] == 1;
    $is_bestseller = isset($book['sales_count']) && $book['sales_count'] > 50;
    $discount_percent = $original_price ? round((($book['original_price'] - $book['price']) / $book['original_price']) * 100) : 0;
    
    echo '<div class="book-card-enhanced position-relative">';
    
    // Enhanced badges
    if ($show_badges) {
        echo '<div class="book-badges">';
        if ($is_featured) echo '<span class="badge badge-featured"><i class="bi bi-star-fill"></i> Featured</span>';
        if ($is_bestseller) echo '<span class="badge badge-bestseller"><i class="bi bi-fire"></i> Bestseller</span>';
        if ($discount_percent > 0) echo '<span class="badge badge-discount">-' . $discount_percent . '%</span>';
        echo '</div>';
    }
    
    // Enhanced image with overlay
    echo '<div class="book-image-wrapper">';
    echo '<img src="' . $image_url . '" class="book-image" alt="' . $title . '" loading="lazy">';
    echo '<div class="book-overlay">';
    echo '<button class="btn btn-outline-light btn-sm quick-view-btn" data-product-id="' . ($book['id'] ?? '') . '" aria-label="Quick view ' . $title . '">';
    echo '<i class="bi bi-eye"></i> Quick View';
    echo '</button>';
    echo '</div>';
    echo '</div>';
    
    // Enhanced content
    echo '<div class="book-content">';
    echo '<h3 class="book-title">' . $title . '</h3>';
    echo '<p class="book-author">by ' . $author . '</p>';
    
    // Enhanced rating
    if ($show_rating && $rating > 0) {
        echo '<div class="book-rating" role="img" aria-label="Rating: ' . $rating . ' out of 5 stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                echo '<i class="bi bi-star-fill" aria-hidden="true"></i>';
            } elseif ($i - 0.5 <= $rating) {
                echo '<i class="bi bi-star-half" aria-hidden="true"></i>';
            } else {
                echo '<i class="bi bi-star" aria-hidden="true"></i>';
            }
        }
        echo '<span class="rating-text">(' . $reviews_count . ' reviews)</span>';
        echo '</div>';
    }
    
    // Enhanced price
    echo '<div class="book-price">';
    echo '<span class="current-price">$' . $price . '</span>';
    if ($original_price) {
        echo '<span class="original-price">$' . $original_price . '</span>';
    }
    echo '</div>';
    
    // Enhanced actions with accessibility
    echo '<div class="book-actions">';
    echo '<button class="btn btn-primary add-to-cart-btn" data-product-id="' . ($book['id'] ?? '') . '" aria-label="Add ' . $title . ' to cart">';
    echo '<i class="bi bi-cart-plus" aria-hidden="true"></i> Add to Cart';
    echo '</button>';
    echo '<button class="btn btn-outline-secondary wishlist-btn" data-product-id="' . ($book['id'] ?? '') . '" aria-label="Add ' . $title . ' to wishlist" data-tooltip="Add to Wishlist">';
    echo '<i class="bi bi-heart" aria-hidden="true"></i>';
    echo '</button>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}
?>
?>

<!-- Skip to content for accessibility -->
<a href="#main-content" class="skip-to-content">Skip to main content</a>

<!-- ENHANCED HERO BANNER SECTION -->
<section id="main-content" class="hero-section-enhanced position-relative overflow-hidden">
    <!-- Animated Background -->
    <div class="hero-background">
        <div class="gradient-overlay"></div>
        <div class="hero-particles" data-parallax="0.3"></div>
    </div>
    
    <div class="container-fluid px-0">
        <div class="row g-0 min-vh-100 align-items-center">
            <!-- Content Column -->
            <div class="col-lg-6 col-xl-5 offset-xl-1">
                <div class="hero-content text-white p-5">
                    <!-- Hero Badge -->
                    <div class="hero-badge mb-4">
                        <span class="badge-text">📚 Discover Your Next Great Read</span>
                        <div class="badge-glow"></div>
                    </div>
                    
                    <!-- Main Heading with Animation -->
                    <h1 class="hero-title mb-4">
                        <span class="title-line-1">Your Literary</span>
                        <span class="title-line-2 text-gradient">Adventure</span>
                        <span class="title-line-3">Starts Here</span>
                    </h1>
                    
                    <!-- Enhanced Subtitle -->
                    <p class="hero-subtitle mb-5">
                        Discover millions of books from bestselling authors, hidden gems, 
                        and exclusive collections. Start your reading journey with our 
                        carefully curated bookstore.
                    </p>
                    
                    <!-- Enhanced CTA Buttons -->
                    <div class="hero-cta mb-5">
                        <a href="/bookshelf/shop.php" class="btn btn-hero-primary me-3">
                            <i class="bi bi-compass me-2" aria-hidden="true"></i>
                            <span>Explore Collection</span>
                            <div class="btn-shine"></div>
                        </a>
                        <a href="#featured-books" class="btn btn-hero-secondary">
                            <i class="bi bi-star me-2" aria-hidden="true"></i>
                            <span>Featured Books</span>
                        </a>
                    </div>
                    
                    <!-- Animated Statistics -->
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number" data-count="10000">0</div>
                            <div class="stat-label">Books Available</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number" data-count="50">0</div>
                            <div class="stat-label">Categories</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-number" data-count="25000">0</div>
                            <div class="stat-label">Happy Readers</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Enhanced Visual Column -->
            <div class="col-lg-6 col-xl-6">
                <div class="hero-visual">
                    <!-- Floating book cards with better images -->
                    <div class="floating-books">
                        <div class="book-float book-1 will-change-transform">
                            <img src="https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=200&h=300&fit=crop" 
                                 alt="Book Cover 1" loading="lazy">
                        </div>
                        <div class="book-float book-2 will-change-transform">
                            <img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=200&h=300&fit=crop" 
                                 alt="Book Cover 2" loading="lazy">
                        </div>
                        <div class="book-float book-3 will-change-transform">
                            <img src="https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=200&h=300&fit=crop" 
                                 alt="Book Cover 3" loading="lazy">
                        </div>
                        <div class="book-float book-4 will-change-transform">
                            <img src="https://images.unsplash.com/photo-1592496431122-2349e0fbc666?w=200&h=300&fit=crop" 
                                 alt="Book Cover 4" loading="lazy">
                        </div>
                    </div>
                    
                    <!-- Central hero image -->
                    <div class="hero-main-image">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=600&fit=crop" 
                             alt="Reading Experience" class="img-fluid" loading="lazy">
                        <div class="image-glow"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Scroll indicator -->
    <div class="scroll-indicator">
        <div class="scroll-text">Scroll to explore our collection</div>
        <div class="scroll-arrow">
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </div>
    </div>
</section>

<!-- ENHANCED SEARCH SECTION -->
<section class="search-section-enhanced py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-container">
                    <h2 class="search-title text-center mb-4">What are you looking for?</h2>
                    
                    <!-- Enhanced Search Form -->
                    <form class="advanced-search-form" action="/bookshelf/shop.php" method="GET" role="search">
                        <div class="search-input-group">
                            <div class="search-icon">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   class="search-input" 
                                   placeholder="Search by title, author, ISBN, or keywords..."
                                   id="mainSearchInput"
                                   autocomplete="off"
                                   aria-label="Search books">
                            <div class="search-filters">
                                <label for="category-select" class="visually-hidden">Category</label>
                                <select name="category" id="category-select" class="search-select" aria-label="Select category">
                                    <option value="">All Categories</option>
                                    <?php 
                                    if ($result_categories && $result_categories->num_rows > 0) {
                                        mysqli_data_seek($result_categories, 0);
                                        while($category = $result_categories->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($category['id']) . "'>" . 
                                                 htmlspecialchars($category['name'] ?? '') . "</option>";
                                        }
                                        mysqli_data_seek($result_categories, 0);
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="search-button" aria-label="Search">
                                <span>Search</span>
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                        
                        <!-- Enhanced search suggestions dropdown -->
                        <div class="search-suggestions" id="searchSuggestions" role="listbox" aria-label="Search suggestions"></div>
                    </form>
                    
                    <!-- Enhanced Quick Search Tags -->
                    <div class="quick-search-tags mt-4">
                        <span class="tags-label">Popular searches:</span>
                        <a href="/bookshelf/shop.php?search=bestseller" class="search-tag">Bestsellers</a>
                        <a href="/bookshelf/shop.php?search=fiction" class="search-tag">Fiction</a>
                        <a href="/bookshelf/shop.php?search=romance" class="search-tag">Romance</a>
                        <a href="/bookshelf/shop.php?search=mystery" class="search-tag">Mystery</a>
                        <a href="/bookshelf/shop.php?search=self-help" class="search-tag">Self Help</a>
                        <a href="/bookshelf/shop.php?search=biography" class="search-tag">Biography</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ENHANCED CATEGORIES SECTION -->
<section id="categories" class="categories-section-enhanced py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title-enhanced">Explore Book Categories</h2>
            <p class="section-subtitle">Discover your next favorite book across our diverse collection of genres and topics</p>
        </div>
        
        <div class="categories-grid">
            <?php 
            if ($result_categories && $result_categories->num_rows > 0) {
                $category_icons = [
                    'Fiction' => 'book-half',
                    'Romance' => 'heart-fill',
                    'Mystery' => 'search',
                    'Science Fiction' => 'rocket-takeoff',
                    'Biography' => 'person-circle',
                    'Children' => 'emoji-smile',
                    'Business' => 'graph-up-arrow',
                    'Self Help' => 'lightbulb',
                    'Non-Fiction' => 'journal-text',
                    'Thriller' => 'lightning-fill'
                ];
                
                $category_colors = [
                    'Fiction' => 'from-blue-500 to-purple-600',
                    'Romance' => 'from-pink-500 to-red-500',
                    'Mystery' => 'from-gray-600 to-gray-800',
                    'Science Fiction' => 'from-cyan-500 to-blue-600',
                    'Biography' => 'from-green-500 to-teal-600',
                    'Children' => 'from-yellow-400 to-orange-500',
                    'Business' => 'from-indigo-500 to-purple-600',
                    'Self Help' => 'from-emerald-500 to-green-600',
                    'Non-Fiction' => 'from-blue-400 to-indigo-600',
                    'Thriller' => 'from-red-600 to-orange-600'
                ];
                
                while($category = $result_categories->fetch_assoc()) {
                    $cat_name = $category['name'] ?? 'Category';
                    $icon = $category_icons[$cat_name] ?? 'book';
                    $color_class = $category_colors[$cat_name] ?? 'from-blue-500 to-purple-600';
                    $description = $category['description'] ?? 'Explore amazing books in this category';
            ?>
                <div class="category-card-enhanced" data-animate="fadeInUp">
                    <a href="/bookshelf/shop.php?category=<?php echo htmlspecialchars($category['id']); ?>" 
                       class="category-link" 
                       aria-label="Browse <?php echo htmlspecialchars($cat_name); ?> books">
                        <div class="category-gradient bg-gradient-to-br <?php echo $color_class; ?>"></div>
                        <div class="category-content">
                            <div class="category-icon-wrapper">
                                <i class="bi bi-<?php echo $icon; ?> category-icon" aria-hidden="true"></i>
                            </div>
                            <h3 class="category-name"><?php echo htmlspecialchars($cat_name); ?></h3>
                            <p class="category-description">
                                <?php echo htmlspecialchars($description); ?>
                            </p>
                            <div class="category-arrow">
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="category-hover-effect"></div>
                    </a>
                </div>
            <?php 
                }
            } else {
                // Enhanced default categories
                $default_categories = [
                    ['name' => 'Fiction', 'icon' => 'book-half', 'color' => 'from-blue-500 to-purple-600', 'desc' => 'Immerse yourself in captivating stories and literary worlds'],
                    ['name' => 'Romance', 'icon' => 'heart-fill', 'color' => 'from-pink-500 to-red-500', 'desc' => 'Love stories that touch the heart and soul'],
                    ['name' => 'Mystery', 'icon' => 'search', 'color' => 'from-gray-600 to-gray-800', 'desc' => 'Thrilling mysteries and suspenseful tales'],
                    ['name' => 'Sci-Fi', 'icon' => 'rocket-takeoff', 'color' => 'from-cyan-500 to-blue-600', 'desc' => 'Journey to other worlds and future possibilities'],
                    ['name' => 'Biography', 'icon' => 'person-circle', 'color' => 'from-green-500 to-teal-600', 'desc' => 'Inspiring life stories and personal journeys'],
                    ['name' => 'Children', 'icon' => 'emoji-smile', 'color' => 'from-yellow-400 to-orange-500', 'desc' => 'Delightful books for young readers and families'],
                    ['name' => 'Business', 'icon' => 'graph-up-arrow', 'color' => 'from-indigo-500 to-purple-600', 'desc' => 'Professional development and entrepreneurship'],
                    ['name' => 'Self Help', 'icon' => 'lightbulb', 'color' => 'from-emerald-500 to-green-600', 'desc' => 'Personal growth and life improvement guides']
                ];
                
                foreach($default_categories as $cat) {
            ?>
                <div class="category-card-enhanced" data-animate="fadeInUp">
                    <a href="/bookshelf/shop.php" class="category-link" aria-label="Browse <?php echo $cat['name']; ?> books">
                        <div class="category-gradient bg-gradient-to-br <?php echo $cat['color']; ?>"></div>
                        <div class="category-content">
                            <div class="category-icon-wrapper">
                                <i class="bi bi-<?php echo $cat['icon']; ?> category-icon" aria-hidden="true"></i>
                            </div>
                            <h3 class="category-name"><?php echo $cat['name']; ?></h3>
                            <p class="category-description"><?php echo $cat['desc']; ?></p>
                            <div class="category-arrow">
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="category-hover-effect"></div>
                    </a>
                </div>
            <?php 
                }
            }
            ?>
        </div>
        
        <!-- View All Categories Button -->
        <div class="text-center mt-5">
            <a href="/bookshelf/shop.php" class="btn btn-premium">
                <span>View All Categories</span>
                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

<!-- ENHANCED FEATURED PRODUCTS SECTION -->
<?php 
// Debug: Check what we have
$has_featured = $result_featured && $result_featured->num_rows > 0;
if (!$has_featured) {
    error_log("No featured books found, showing fallback section");
}
?>
<?php if ($has_featured) { ?>
<section id="featured-books" class="featured-section-enhanced py-5">
    <div class="container">
        <div class="section-header-enhanced text-center mb-5">
            <div class="section-badge">
                <i class="bi bi-star-fill" aria-hidden="true"></i>
                Editor's Choice
            </div>
            <h2 class="section-title-large">Featured Books</h2>
            <p class="section-subtitle">
                Handpicked selections from our literary experts and bestselling authors, 
                carefully curated to bring you the finest reading experiences.
            </p>
        </div>
        
        <!-- Enhanced Featured Books Carousel -->
        <div class="featured-books-grid">
            <div class="featured-carousel-container">
                <div class="featured-books-carousel" id="featuredBooksCarousel">
                    <?php 
                    mysqli_data_seek($result_featured, 0);
                    $count = 0;
                    while(($book = $result_featured->fetch_assoc()) && $count < 8) { 
                    ?>
                    <div class="carousel-item">
                        <?php render_enhanced_book_card($book); ?>
                    </div>
                    <?php 
                        $count++;
                    } 
                    ?>
                </div>
                
                <!-- Carousel Progress Indicator -->
                <div class="carousel-progress" id="carouselProgress"></div>
            </div>
            
            <!-- Enhanced Carousel Controls -->
            <div class="carousel-controls">
                <button class="carousel-btn carousel-prev" id="featuredPrev" aria-label="Previous books">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                
                <!-- Carousel Dots -->
                <div class="carousel-dots" id="carouselDots"></div>
                
                <button class="carousel-btn carousel-next" id="featuredNext" aria-label="Next books">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        
        <!-- Enhanced Call-to-Action -->
        <div class="text-center mt-5">
            <a href="/bookshelf/shop.php?featured=1" class="btn btn-premium position-relative">
                <span>Discover All Featured Books</span>
                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                <div class="btn-glow"></div>
            </a>
        </div>
    </div>
</section>

<!-- Enhanced JavaScript for Featured Carousel -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('featuredBooksCarousel');
    const prevBtn = document.getElementById('featuredPrev');
    const nextBtn = document.getElementById('featuredNext');
    const dotsContainer = document.getElementById('carouselDots');
    const progressBar = document.getElementById('carouselProgress');
    
    if (!carousel || !prevBtn || !nextBtn) return;
    
    let currentIndex = 0;
    let isAnimating = false;
    let autoPlayInterval;
    const items = carousel.querySelectorAll('.carousel-item');
    const itemsToShow = getItemsToShow();
    const totalSlides = Math.max(0, items.length - itemsToShow + 1);
    
    // Enhanced mobile-responsive carousel settings
    function getItemsToShow() {
        if (window.innerWidth >= 1441) return 4;
        if (window.innerWidth >= 1025) return 3;
        if (window.innerWidth >= 769) return 2;
        if (window.innerWidth >= 481) return 2;
        return 1;
    }
    
    // Mobile-optimized initialization
    function initializeMobileFeatures() {
        // Add touch-friendly visual feedback
        const buttons = document.querySelectorAll('.carousel-btn, .quick-view-btn, .add-to-cart-btn');
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            }, { passive: true });
        });
        
        // Improve scroll performance
        if ('scrollBehavior' in document.documentElement.style) {
            document.documentElement.style.scrollBehavior = 'smooth';
        }
        
        // Add loading states for better UX
        const carouselContainer = document.querySelector('.featured-carousel-container');
        if (carouselContainer) {
            carouselContainer.style.opacity = '0';
            carouselContainer.style.transition = 'opacity 0.3s ease';
            
            // Show when loaded
            setTimeout(() => {
                carouselContainer.style.opacity = '1';
            }, 100);
        }
    }
    
    function createDots() {
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('button');
            dot.className = 'carousel-dot';
            dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
            if (i === currentIndex) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }
    
    function updateCarousel(smooth = true) {
        if (isAnimating) return;
        
        const itemWidth = items[0].offsetWidth + 32; // item width + gap
        const translateX = -currentIndex * itemWidth;
        
        if (smooth) {
            isAnimating = true;
            carousel.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        } else {
            carousel.style.transition = 'none';
        }
        
        carousel.style.transform = `translateX(${translateX}px)`;
        
        // Update controls
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= totalSlides - 1;
        
        // Update dots
        document.querySelectorAll('.carousel-dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
        
        // Update progress
        const progress = ((currentIndex + 1) / totalSlides) * 100;
        progressBar.style.width = `${progress}%`;
        
        if (smooth) {
            setTimeout(() => {
                isAnimating = false;
            }, 600);
        }
    }
    
    function goToSlide(index) {
        if (index >= 0 && index < totalSlides && index !== currentIndex) {
            currentIndex = index;
            updateCarousel();
            resetAutoPlay();
        }
    }
    
    function nextSlide() {
        if (currentIndex < totalSlides - 1) {
            currentIndex++;
        } else {
            currentIndex = 0; // Loop back to start
        }
        updateCarousel();
    }
    
    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
        } else {
            currentIndex = totalSlides - 1; // Loop to end
        }
        updateCarousel();
    }
    
    function startAutoPlay() {
        stopAutoPlay();
        autoPlayInterval = setInterval(() => {
            nextSlide();
        }, 5000);
    }
    
    function stopAutoPlay() {
        if (autoPlayInterval) {
            clearInterval(autoPlayInterval);
            autoPlayInterval = null;
        }
    }
    
    function resetAutoPlay() {
        stopAutoPlay();
        startAutoPlay();
    }
    
    // Event listeners
    prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAutoPlay();
    });
    
    nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAutoPlay();
    });
    
    // Enhanced touch/swipe support with better mobile experience
    let startX = 0;
    let startY = 0;
    let isDragging = false;
    let startTime = 0;
    
    carousel.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isDragging = true;
        startTime = Date.now();
        stopAutoPlay();
        
        // Add visual feedback
        carousel.style.transition = 'none';
    }, { passive: true });
    
    carousel.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        
        const currentX = e.touches[0].clientX;
        const currentY = e.touches[0].clientY;
        const deltaX = startX - currentX;
        const deltaY = startY - currentY;
        
        // Prevent vertical scroll if horizontal swipe is dominant
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
            e.preventDefault();
        }
    });
    
    carousel.addEventListener('touchend', (e) => {
        if (!isDragging) return;
        
        const endX = e.changedTouches[0].clientX;
        const endTime = Date.now();
        const diff = startX - endX;
        const timeDiff = endTime - startTime;
        const velocity = Math.abs(diff) / timeDiff;
        
        // Reset transition
        carousel.style.transition = 'transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        
        // Swipe detection with velocity consideration
        if (Math.abs(diff) > 30 || velocity > 0.3) {
            if (diff > 0 && currentIndex < totalSlides - 1) {
                nextSlide();
            } else if (diff < 0 && currentIndex > 0) {
                prevSlide();
            }
        }
        
        isDragging = false;
        startAutoPlay();
    }, { passive: true });
    
    // Pause auto-play on hover
    carousel.addEventListener('mouseenter', stopAutoPlay);
    carousel.addEventListener('mouseleave', startAutoPlay);
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.target.closest('#featured-books')) {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prevSlide();
                resetAutoPlay();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                nextSlide();
                resetAutoPlay();
            }
        }
    });
    
    // Initialize carousel
    if (items.length > 0) {
        createDots();
        updateCarousel(false);
        startAutoPlay();
        initializeMobileFeatures();
    }
    
    // Enhanced responsive handling
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const newItemsToShow = getItemsToShow();
            const newTotalSlides = Math.max(0, items.length - newItemsToShow + 1);
            
            if (newTotalSlides !== totalSlides) {
                // Recalculate carousel settings
                itemsToShow = newItemsToShow;
                totalSlides = newTotalSlides;
                
                // Adjust current index if needed
                if (currentIndex >= totalSlides) {
                    currentIndex = Math.max(0, totalSlides - 1);
                }
                
                createDots();
                updateCarousel(false);
            }
        }, 150);
    });

});
</script>
<?php } ?>

<!-- Fallback Featured Books Section - Always Show if no database books -->
<?php if (!$has_featured) { ?>
<section id="featured-books" class="featured-section-enhanced py-5">
    <div class="container">
        <div class="section-header-enhanced text-center mb-5">
            <div class="section-badge">
                <i class="bi bi-star-fill" aria-hidden="true"></i>
                Editor's Choice
            </div>
            <h2 class="section-title-large">Featured Books</h2>
            <p class="section-subtitle">
                Discover amazing books in our carefully curated collection.
            </p>
        </div>
        
        <!-- Sample Featured Books Grid -->
        <div class="featured-books-grid">
            <div class="row g-4">
                <?php 
                // Sample books data when no database records exist
                $sample_books = [
                    [
                        'id' => 1,
                        'title' => 'The Great Gatsby',
                        'author' => 'F. Scott Fitzgerald',
                        'price' => 12.99,
                        'original_price' => 16.99,
                        'rating' => 4.5,
                        'reviews_count' => 234,
                        'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 2,
                        'title' => 'To Kill a Mockingbird',
                        'author' => 'Harper Lee',
                        'price' => 14.99,
                        'rating' => 4.8,
                        'reviews_count' => 456,
                        'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 3,
                        'title' => '1984',
                        'author' => 'George Orwell',
                        'price' => 13.99,
                        'original_price' => 17.99,
                        'rating' => 4.7,
                        'reviews_count' => 789,
                        'image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 4,
                        'title' => 'Pride and Prejudice',
                        'author' => 'Jane Austen',
                        'price' => 11.99,
                        'rating' => 4.6,
                        'reviews_count' => 345,
                        'image_url' => 'https://images.unsplash.com/photo-1592496431122-2349e0fbc666?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 5,
                        'title' => 'The Catcher in the Rye',
                        'author' => 'J.D. Salinger',
                        'price' => 15.99,
                        'rating' => 4.3,
                        'reviews_count' => 567,
                        'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 6,
                        'title' => 'Harry Potter',
                        'author' => 'J.K. Rowling',
                        'price' => 18.99,
                        'original_price' => 22.99,
                        'rating' => 4.9,
                        'reviews_count' => 892,
                        'image_url' => 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 7,
                        'title' => 'The Lord of the Rings',
                        'author' => 'J.R.R. Tolkien',
                        'price' => 25.99,
                        'original_price' => 29.99,
                        'rating' => 4.8,
                        'reviews_count' => 1234,
                        'image_url' => 'https://images.unsplash.com/photo-1541963463532-d68292c34d19?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ],
                    [
                        'id' => 8,
                        'title' => 'Dune',
                        'author' => 'Frank Herbert',
                        'price' => 16.99,
                        'rating' => 4.6,
                        'reviews_count' => 445,
                        'image_url' => 'https://images.unsplash.com/photo-1568667256549-094345857637?w=300&h=400&fit=crop',
                        'is_featured' => 1
                    ]
                ];
                
                foreach($sample_books as $book) {
                    echo '<div class="col-lg-3 col-md-6 mb-4">';
                    render_enhanced_book_card($book);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <!-- Enhanced Call-to-Action -->
        <div class="text-center mt-5">
            <a href="/bookshelf/shop.php" class="btn btn-premium position-relative">
                <span>Explore All Books</span>
                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                <div class="btn-glow"></div>
            </a>
        </div>
    </div>
</section>
<?php } ?>

<!-- ENHANCED BESTSELLERS SECTION -->
<section class="bestsellers-section py-5 bg-light">
    <div class="container">
        <div class="section-header-enhanced text-center mb-5">
            <div class="section-badge">🔥 Trending Now</div>
            <h2 class="section-title-large">Bestselling Books</h2>
            <p class="section-subtitle">The most loved books by our community of readers worldwide</p>
        </div>
        
        <!-- Enhanced Bestsellers Grid -->
        <div class="bestsellers-grid">
            <?php
            // Use bestsellers if we have them, otherwise show featured or sample data
            if (isset($result_bestsellers) && $result_bestsellers && $result_bestsellers->num_rows > 0) {
                while($book = $result_bestsellers->fetch_assoc()) {
                    render_enhanced_book_card($book);
                }
            } elseif ($result_featured && $result_featured->num_rows > 0) {
                // Show featured books as bestsellers if no dedicated bestsellers
                mysqli_data_seek($result_featured, 0);
                $count = 0;
                while($book = $result_featured->fetch_assoc() && $count < 8) {
                    $book['sales_count'] = rand(50, 200); // Add mock sales count
                    render_enhanced_book_card($book);
                    $count++;
                }
            } else {
                // Enhanced sample books with realistic data
                $sample_books = [
                    [
                        'id' => 1, 
                        'title' => 'The Seven Husbands of Evelyn Hugo', 
                        'author' => 'Taylor Jenkins Reid', 
                        'price' => 16.99, 
                        'original_price' => 19.99, 
                        'rating' => 4.6, 
                        'reviews_count' => 89251, 
                        'sales_count' => 156,
                        'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=400&fit=crop'
                    ],
                    [
                        'id' => 2, 
                        'title' => 'Where the Crawdads Sing', 
                        'author' => 'Delia Owens', 
                        'price' => 15.99, 
                        'rating' => 4.5, 
                        'reviews_count' => 76234, 
                        'sales_count' => 134,
                        'image_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=400&fit=crop'
                    ],
                    [
                        'id' => 3, 
                        'title' => 'The Midnight Library', 
                        'author' => 'Matt Haig', 
                        'price' => 14.99, 
                        'original_price' => 17.99, 
                        'rating' => 4.3, 
                        'reviews_count' => 52198, 
                        'sales_count' => 112,
                        'image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=300&h=400&fit=crop'
                    ],
                    [
                        'id' => 4, 
                        'title' => 'Educated', 
                        'author' => 'Tara Westover', 
                        'price' => 17.99, 
                        'rating' => 4.7, 
                        'reviews_count' => 68431, 
                        'sales_count' => 98,
                        'image_url' => 'https://images.unsplash.com/photo-1592496431122-2349e0fbc666?w=300&h=400&fit=crop'
                    ],
                    [
                        'id' => 5, 
                        'title' => 'The Silent Patient', 
                        'author' => 'Alex Michaelides', 
                        'price' => 15.49, 
                        'rating' => 4.4, 
                        'reviews_count' => 41287, 
                        'sales_count' => 87,
                        'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=400&fit=crop'
                    ],
                    [
                        'id' => 6, 
                        'title' => 'Atomic Habits', 
                        'author' => 'James Clear', 
                        'price' => 18.99, 
                        'rating' => 4.8, 
                        'reviews_count' => 95412, 
                        'sales_count' => 203,
                        'image_url' => 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=300&h=400&fit=crop'
                    ]
                ];
                
                foreach($sample_books as $book) {
                    render_enhanced_book_card($book);
                }
            }
            ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="/bookshelf/shop.php?sort=bestseller" class="btn btn-premium">
                <span>View All Bestsellers</span>
                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

<!-- ENHANCED PROMOTIONAL SECTION -->
<section class="promo-section py-5">
    <div class="container">
        <div class="promo-grid">
            <!-- Main Promotional Banner -->
            <div class="promo-main">
                <div class="promo-content">
                    <div class="promo-badge">Limited Time Offer</div>
                    <h3 class="promo-title">Book Lover's Paradise</h3>
                    <p class="promo-description">
                        Join our exclusive reading community and unlock special discounts, 
                        early access to new releases, and personalized book recommendations 
                        tailored just for you.
                    </p>
                    <div class="promo-features">
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>Free shipping on orders over ₹25</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>Exclusive member discounts up to 30%</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>Early access to new releases</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <span>Personalized reading recommendations</span>
                        </div>
                    </div>
                    <a href="/bookshelf/shop.php" class="btn btn-promo">
                        <span>Start Shopping</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="promo-visual">
                    <img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=400&h=300&fit=crop" 
                         alt="Stack of books" class="promo-image">
                </div>
            </div>
            
            <!-- Side Promotional Cards -->
            <div class="promo-side">
                <div class="promo-card promo-deals">
                    <div class="promo-icon">
                        <i class="bi bi-percent" aria-hidden="true"></i>
                    </div>
                    <h4>Daily Deals</h4>
                    <p>Up to 50% off selected titles. New deals every day!</p>
                    <a href="/bookshelf/shop.php?sort=price_asc" class="btn btn-outline-primary btn-sm">
                        Shop Deals
                    </a>
                </div>
                
                <div class="promo-card promo-newsletter">
                    <div class="promo-icon">
                        <i class="bi bi-envelope-heart" aria-hidden="true"></i>
                    </div>
                    <h4>Newsletter</h4>
                    <p>Get personalized book recommendations & exclusive offers</p>
                    <button class="btn btn-outline-primary btn-sm" 
                            onclick="document.getElementById('newsletterForm').scrollIntoView({behavior: 'smooth'})">
                        Subscribe Now
                    </button>
                </div>
                
                <div class="promo-card promo-reviews">
                    <div class="promo-icon">
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                    </div>
                    <h4>Reader Reviews</h4>
                    <p>Join thousands of readers sharing their favorite discoveries</p>
                    <a href="/bookshelf/shop.php" class="btn btn-outline-primary btn-sm">
                        Read Reviews
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>




<!-- ENHANCED NEWSLETTER SUBSCRIPTION -->
<section class="newsletter-section py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h3 class="mb-3 text-white">📧 Stay Connected with Great Reads</h3>
                <p class="mb-4 text-white">
                    Subscribe to our newsletter for new arrivals, exclusive offers, 
                    personalized reading recommendations, and literary insights delivered to your inbox.
                </p>
                <form class="d-flex justify-content-center" id="newsletterForm" novalidate>
                    <div class="input-group input-group-lg" style="max-width: 500px;">
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               placeholder="Enter your email address" 
                               required 
                               aria-label="Email address">
                        <button class="btn btn-warning px-4" type="submit" aria-label="Subscribe to newsletter">
                            <span>Subscribe</span>
                            <i class="bi bi-envelope-plus ms-2" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <small class="text-white-50 mt-3 d-block">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>
                    We respect your privacy. Unsubscribe at any time. No spam, we promise!
                </small>
                
                <!-- Newsletter Benefits -->
                <div class="row mt-4 text-start">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center text-white mb-2">
                            <i class="bi bi-book-half me-2" aria-hidden="true"></i>
                            <span>Weekly book recommendations</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center text-white mb-2">
                            <i class="bi bi-percent me-2" aria-hidden="true"></i>
                            <span>Exclusive discounts & offers</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center text-white mb-2">
                            <i class="bi bi-lightning-charge me-2" aria-hidden="true"></i>
                            <span>Early access to new releases</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced JavaScript Integration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhanced features if script.js is loaded
    if (typeof bookstoreApp !== 'undefined') {
        console.log('Enhanced bookstore features loaded successfully');
    }
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});
</script>

<!-- Professional Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-labelledby="quickViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center" id="quickViewModalLabel">
                    <i class="bi bi-eye me-2 text-primary"></i>
                    Quick View
                </h5>
                <button type="button" class="btn-close btn-close-white bg-light rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="quickViewContent">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mb-0">Loading product details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Professional Quick View Implementation
document.addEventListener('DOMContentLoaded', function() {
    // Initialize quick view functionality
    initializeQuickView();
    
    function initializeQuickView() {
        const quickViewButtons = document.querySelectorAll('.quick-view-btn');
        
        quickViewButtons.forEach(button => {
            button.addEventListener('click', handleQuickView);
        });
    }
    
    function handleQuickView(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const productId = e.currentTarget.dataset.productId;
        
        if (!productId) {
            console.error('Product ID not found');
            return;
        }
        
        loadQuickView(productId);
    }
    
    function loadQuickView(productId) {
        const modal = new bootstrap.Modal(document.getElementById('quickViewModal'), {
            backdrop: 'static',
            keyboard: true
        });
        const content = document.getElementById('quickViewContent');
        
        // Show loading state with enhanced animation
        content.innerHTML = `
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mb-0">Loading product details...</p>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Load product data with error handling
        fetch(`/bookshelf/ajax/get_product_quick_view.php?id=${productId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                    
                    // Add fade-in animation
                    content.style.opacity = '0';
                    content.style.transition = 'opacity 0.3s ease-in-out';
                    setTimeout(() => {
                        content.style.opacity = '1';
                    }, 100);
                    
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger m-4 d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong>Error!</strong> ${data.message || 'Failed to load product details.'}
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Quick view error:', error);
                content.innerHTML = `
                    <div class="alert alert-danger m-4 d-flex align-items-center" role="alert">
                        <i class="bi bi-wifi-off me-2"></i>
                        <div>
                            <strong>Connection Error!</strong> Please check your internet connection and try again.
                        </div>
                    </div>
                `;
            });
    }
    
    // Global functions for quick view actions
    window.updateQuantity = function(change) {
        const input = document.getElementById('quickViewQuantity');
        if (!input) return;
        
        const newValue = parseInt(input.value) + change;
        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 999;
        
        if (newValue >= min && newValue <= max) {
            input.value = newValue;
        }
    };
    
    window.addToCartFromQuickView = function(productId) {
        const quantityInput = document.getElementById('quickViewQuantity');
        const quantity = quantityInput ? quantityInput.value : 1;
        const button = event.target.closest('button');
        
        // Add loading state
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
            button.disabled = true;
            
            // Reset button after timeout as fallback
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 5000);
        }
        
        if (typeof window.addToCart === 'function') {
            window.addToCart(productId, quantity).then(() => {
                // Close modal on success
                const modal = bootstrap.Modal.getInstance(document.getElementById('quickViewModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Show success notification
                showNotification('Product added to cart successfully!', 'success');
            }).catch(() => {
                if (button) {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            });
        } else {
            // Fallback to form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/bookshelf/ajax/add_to_cart.php';
            form.innerHTML = `
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="${quantity}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
    
    window.addToWishlistFromQuickView = function(productId) {
        const button = event.target.closest('button');
        
        if (typeof window.addToWishlist === 'function') {
            window.addToWishlist(productId);
        } else {
            // Fallback for wishlist
            fetch('/bookshelf/ajax/add_to_wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Added to wishlist!', 'success');
                    if (button) {
                        button.innerHTML = '<i class="bi bi-heart-fill"></i>';
                        button.classList.add('btn-danger');
                        button.classList.remove('btn-outline-danger');
                    }
                } else {
                    showNotification(data.message || 'Failed to add to wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Wishlist error:', error);
                showNotification('Failed to add to wishlist', 'error');
            });
        }
    };
    
    // Notification function
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} notification-toast`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill'} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 4000);
        
        // Manual close
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
