<?php
// Create this file as: includes/shop_filters.php
// Shop filters sidebar component

// Current filter values
$current_search = $_GET['search'] ?? '';
$current_category = $_GET['category'] ?? '';
$current_min_price = $_GET['min_price'] ?? '';
$current_max_price = $_GET['max_price'] ?? '';
$current_featured = isset($_GET['featured']);
$current_in_stock = isset($_GET['in_stock']);

// Get price range if not already available
if (!isset($price_range)) {
    $price_range_sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE (status = 'active' OR status IS NULL)";
    $price_range_result = $conn->query($price_range_sql);
    $price_range = $price_range_result->fetch_assoc();
}

$min_price_range = floor($price_range['min_price'] ?? 0);
$max_price_range = ceil($price_range['max_price'] ?? 100);
?>

<form method="GET" action="/bookshelf/shop.php" class="filter-form">
    <!-- Preserve search query -->
    <?php if (!empty($current_search)): ?>
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($current_search); ?>">
    <?php endif; ?>

    <!-- Search Filter -->
    <div class="filter-widget">
        <h6><i class="bi bi-search me-2"></i>Search Books</h6>
        <div class="mb-3">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   placeholder="Search books, authors..."
                   value="<?php echo htmlspecialchars($current_search); ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-search me-2"></i>Search
        </button>
    </div>

    <!-- Category Filter -->
    <div class="filter-widget">
        <h6><i class="bi bi-grid me-2"></i>Categories</h6>
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="category" value="" id="cat_all" 
                   <?php echo empty($current_category) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="cat_all">
                All Categories
            </label>
        </div>
        <?php foreach ($categories as $category): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="category" 
                       value="<?php echo $category['id']; ?>" 
                       id="cat_<?php echo $category['id']; ?>"
                       <?php echo $current_category == $category['id'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                    <?php 
                    // Get product count for this category
                    $count_sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND (status = 'active' OR status IS NULL)";
                    $count_stmt = $conn->prepare($count_sql);
                    $count_stmt->bind_param("i", $category['id']);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count = $count_result->fetch_assoc()['count'];
                    
                    if ($count > 0): ?>
                        <span class="text-muted">(<?php echo $count; ?>)</span>
                    <?php endif; ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Price Range Filter -->
    <div class="filter-widget">
        <h6><i class="bi bi-currency-rupee me-2"></i>Price Range</h6>
        <div class="price-range-container">
            <div class="price-inputs mb-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" 
                           name="min_price" 
                           id="minPrice"
                           class="form-control" 
                           placeholder="Min"
                           min="<?php echo $min_price_range; ?>"
                           max="<?php echo $max_price_range; ?>"
                           value="<?php echo htmlspecialchars($current_min_price); ?>">
                </div>
                <span class="mx-2">to</span>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">$</span>
                    <input type="number" 
                           name="max_price" 
                           id="maxPrice"
                           class="form-control" 
                           placeholder="Max"
                           min="<?php echo $min_price_range; ?>"
                           max="<?php echo $max_price_range; ?>"
                           value="<?php echo htmlspecialchars($current_max_price); ?>">
                </div>
            </div>
            
            <!-- Price range slider -->
            <div class="price-range-slider">
                <input type="range" 
                       class="form-range" 
                       id="priceRange"
                       min="<?php echo $min_price_range; ?>"
                       max="<?php echo $max_price_range; ?>"
                       step="1">
            </div>
            
            <div class="d-flex justify-content-between">
                <small class="text-muted">$<?php echo $min_price_range; ?></small>
                <small class="text-muted">$<?php echo $max_price_range; ?></small>
            </div>
        </div>
    </div>

    <!-- Special Filters -->
    <div class="filter-widget">
        <h6><i class="bi bi-star me-2"></i>Special Options</h6>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="featured" value="1" id="featured"
                   <?php echo $current_featured ? 'checked' : ''; ?>>
            <label class="form-check-label" for="featured">
                <i class="bi bi-star-fill text-warning me-1"></i>Featured Books
            </label>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="in_stock"
                   <?php echo $current_in_stock ? 'checked' : ''; ?>>
            <label class="form-check-label" for="in_stock">
                <i class="bi bi-check-circle text-success me-1"></i>In Stock Only
            </label>
        </div>
        
        <?php
        // Get ratings distribution
        $ratings_sql = "SELECT 
                            FLOOR(rating) as rating_floor, 
                            COUNT(*) as count 
                        FROM products 
                        WHERE rating > 0 AND (status = 'active' OR status IS NULL)
                        GROUP BY FLOOR(rating) 
                        ORDER BY rating_floor DESC";
        $ratings_result = $conn->query($ratings_sql);
        
        if ($ratings_result && $ratings_result->num_rows > 0): ?>
            <div class="mt-3">
                <small class="text-muted fw-medium d-block mb-2">Minimum Rating</small>
                <?php while ($rating_row = $ratings_result->fetch_assoc()): ?>
                    <?php $rating_val = $rating_row['rating_floor']; ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio" name="min_rating" 
                               value="<?php echo $rating_val; ?>" 
                               id="rating_<?php echo $rating_val; ?>"
                               <?php echo (isset($_GET['min_rating']) && $_GET['min_rating'] == $rating_val) ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="rating_<?php echo $rating_val; ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= $rating_val ? '-fill text-warning' : ' text-muted'; ?>"></i>
                            <?php endfor; ?>
                            & up (<?php echo $rating_row['count']; ?>)
                        </label>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Authors Filter (Top Authors) -->
    <?php
    $authors_sql = "SELECT author, COUNT(*) as book_count 
                   FROM products 
                   WHERE author IS NOT NULL AND author != '' AND (status = 'active' OR status IS NULL)
                   GROUP BY author 
                   ORDER BY book_count DESC, author ASC 
                   LIMIT 10";
    $authors_result = $conn->query($authors_sql);
    
    if ($authors_result && $authors_result->num_rows > 0): ?>
        <div class="filter-widget">
            <h6><i class="bi bi-person me-2"></i>Popular Authors</h6>
            <?php while ($author_row = $authors_result->fetch_assoc()): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="authors[]" 
                           value="<?php echo htmlspecialchars($author_row['author']); ?>" 
                           id="author_<?php echo md5($author_row['author']); ?>"
                           <?php echo (isset($_GET['authors']) && in_array($author_row['author'], $_GET['authors'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="author_<?php echo md5($author_row['author']); ?>">
                        <?php echo htmlspecialchars($author_row['author']); ?>
                        <span class="text-muted">(<?php echo $author_row['book_count']; ?>)</span>
                    </label>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <!-- Clear Filters -->
    <div class="filter-widget">
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-funnel me-2"></i>Apply Filters
            </button>
            <a href="/bookshelf/shop.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-2"></i>Clear All
            </a>
        </div>
    </div>
</form>

<style>
/* Filter Sidebar Styles */
.filter-widget {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.filter-widget h6 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.filter-widget .form-check {
    padding-left: 1.5rem;
}

.filter-widget .form-check-input {
    margin-top: 0.25rem;
}

.filter-widget .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.filter-widget .form-check-label {
    font-size: 0.9rem;
    line-height: 1.4;
    cursor: pointer;
}

.price-inputs {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.price-inputs .input-group {
    flex: 1;
}

.price-range-slider {
    margin: 1rem 0 0.5rem 0;
}

.form-range::-webkit-slider-thumb {
    background: #667eea;
}

.form-range::-moz-range-thumb {
    background: #667eea;
    border: none;
}

.form-range:focus::-webkit-slider-thumb {
    box-shadow: 0 0 0 1px #fff, 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
}

.filter-form .btn {
    border-radius: 6px;
    font-weight: 500;
}

.filter-form .btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
}

.filter-form .btn-primary:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
    transform: translateY(-1px);
}

/* Mobile adjustments */
@media (max-width: 991.98px) {
    .filter-widget {
        margin-bottom: 1rem;
        padding: 1rem;
    }
    
    .price-inputs {
        flex-direction: column;
        align-items: stretch;
    }
    
    .price-inputs span {
        text-align: center;
        margin: 0.5rem 0;
    }
}

/* Loading state */
.filter-form.loading {
    opacity: 0.7;
    pointer-events: none;
}

.filter-form.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #667eea;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle authors filter with search
    const authorsContainer = document.querySelector('.filter-widget:has([name="authors[]"])');
    if (authorsContainer && authorsContainer.querySelectorAll('.form-check').length > 5) {
        // Add search for authors if there are many
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control form-control-sm mb-2';
        searchInput.placeholder = 'Search authors...';
        
        const firstCheck = authorsContainer.querySelector('.form-check');
        firstCheck.parentNode.insertBefore(searchInput, firstCheck);
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const checks = authorsContainer.querySelectorAll('.form-check');
            
            checks.forEach(check => {
                const label = check.querySelector('label');
                const authorName = label.textContent.toLowerCase();
                check.style.display = authorName.includes(query) ? 'block' : 'none';
            });
        });
    }
    
    // Price range synchronization
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    const priceRange = document.getElementById('priceRange');
    
    if (minPriceInput && maxPriceInput && priceRange) {
        function updatePriceDisplay() {
            const minVal = parseFloat(minPriceInput.value) || parseInt(priceRange.min);
            const maxVal = parseFloat(maxPriceInput.value) || parseInt(priceRange.max);
            
            // Update range slider to show the price range visually
            const rangePercent = ((minVal + maxVal) / 2 - parseInt(priceRange.min)) / 
                               (parseInt(priceRange.max) - parseInt(priceRange.min)) * 100;
            priceRange.value = minVal + (maxVal - minVal) / 2;
        }
        
        minPriceInput.addEventListener('input', updatePriceDisplay);
        maxPriceInput.addEventListener('input', updatePriceDisplay);
        
        // Initialize
        updatePriceDisplay();
    }
});
</script>