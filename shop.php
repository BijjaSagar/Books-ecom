<?php
// Enhanced shop.php with advanced filtering and modern design
session_start();

// Include necessary files
require_once 'includes/config.php';  // Database connection
require_once 'includes/functions.php';  // All functions

$page_title_override = "Shop Books";
include 'includes/header.php';

// Initialize filters and pagination
$products_per_page = 12;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;

// Filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$featured = isset($_GET['featured']) ? 1 : null;
$in_stock = isset($_GET['in_stock']) ? 1 : null;

// Sorting options
$sort_options = [
    'default' => 'p.created_at DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name_asc' => 'p.title ASC',
    'name_desc' => 'p.title DESC',
    'rating' => 'p.rating DESC',
    'bestseller' => 'p.sales_count DESC',
    'newest' => 'p.created_at DESC'
];

$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_options) ? $_GET['sort'] : 'default';
$order_by = $sort_options[$sort_key];

// Build dynamic WHERE clause
$where_conditions = ["(p.status = 'active' OR p.status IS NULL)"];
$params = [];
$types = "";

// Search filter
if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.author LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

// Category filter
if ($category_id) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Price filters
if ($min_price !== null) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price !== null) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Featured filter
if ($featured) {
    $where_conditions[] = "p.featured = 1";
}

// Stock filter
if ($in_stock) {
    $where_conditions[] = "p.stock_quantity > 0";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total_result = $count_stmt->get_result();
    } else {
        die("Error preparing count query: " . $conn->error);
    }
} else {
    $total_result = $conn->query($count_sql);
}

$total_products = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $products_per_page);

// Get products with filters
$products_sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE $where_clause 
                ORDER BY $order_by 
                LIMIT ? OFFSET ?";

$final_params = array_merge($params, [$products_per_page, $offset]);
$final_types = $types . "ii";

$products_stmt = $conn->prepare($products_sql);
if ($products_stmt) {
    $products_stmt->bind_param($final_types, ...$final_params);
    $products_stmt->execute();
    $result_products = $products_stmt->get_result();
} else {
    die("Error preparing products query: " . $conn->error);
}

// Get categories for filter sidebar - with error handling
$categories = [];
try {
    $categories = get_categories();
} catch (Exception $e) {
    error_log("Error getting categories: " . $e->getMessage());
    // Fallback categories
    $categories = [
        ['id' => 1, 'name' => 'Fiction', 'slug' => 'fiction'],
        ['id' => 2, 'name' => 'Non-Fiction', 'slug' => 'non-fiction'],
        ['id' => 3, 'name' => 'Romance', 'slug' => 'romance'],
        ['id' => 4, 'name' => 'Mystery', 'slug' => 'mystery']
    ];
}

// Get price range for filters
$price_range_sql = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE (status = 'active' OR status IS NULL)";
$price_range_result = $conn->query($price_range_sql);
$price_range = $price_range_result->fetch_assoc();

// Build current filters for display
$active_filters = [];
if (!empty($search)) $active_filters[] = "Search: \"$search\"";
if ($category_id) {
    $category_name = '';
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_id) {
            $category_name = $cat['name'];
            break;
        }
    }
    if ($category_name) $active_filters[] = "Category: $category_name";
}
if ($min_price !== null) $active_filters[] = "Min Price: $" . number_format($min_price, 2);
if ($max_price !== null) $active_filters[] = "Max Price: $" . number_format($max_price, 2);
if ($featured) $active_filters[] = "Featured Only";
if ($in_stock) $active_filters[] = "In Stock Only";

// Build query string for pagination
$query_params = $_GET;
unset($query_params['page']);
$query_string = http_build_query($query_params);
?>



<!-- Breadcrumb -->
<div class="bg-light py-3">
    <div class="container">
        <?php 
        $breadcrumb_items = [
            ['title' => 'Home', 'url' => '/bookshelf/'],
            ['title' => 'Shop', 'url' => null]
        ];
        
        if ($category_id && isset($category_name)) {
            $breadcrumb_items[1]['url'] = '/bookshelf/shop.php';
            $breadcrumb_items[] = ['title' => $category_name, 'url' => null];
        }
        
        render_breadcrumb($breadcrumb_items);
        ?>
    </div>
</div>


<!-- Shop Header -->
<div class="shop-header bg-white py-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h2 fw-bold mb-0">
                    <?php 
                    if (!empty($search)) {
                        echo 'Search Results for "' . htmlspecialchars($search) . '"';
                    } elseif ($category_id && isset($category_name)) {
                        echo htmlspecialchars($category_name) . ' Books';
                    } elseif ($featured) {
                        echo 'Featured Books';
                    } else {
                        echo 'All Books';
                    }
                    ?>
                </h1>
                <p class="text-muted mb-0">
                    Showing <?php echo number_format($total_products); ?> 
                    <?php echo $total_products === 1 ? 'book' : 'books'; ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <button class="btn btn-outline-primary d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterSidebar">
                    <i class="bi bi-funnel me-2"></i>Filters
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Active Filters -->
<?php if (!empty($active_filters)): ?>
<div class="active-filters bg-light py-3">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-medium">Active Filters:</span>
            <?php foreach ($active_filters as $filter): ?>
                <span class="badge bg-primary"><?php echo htmlspecialchars($filter); ?></span>
            <?php endforeach; ?>
            <a href="/bookshelf/shop.php" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="bi bi-x-circle me-1"></i>Clear All
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container my-5">
    <div class="row">
        <!-- Desktop Filter Sidebar -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="filter-sidebar sticky-top" style="top: 120px;">
                <?php include 'includes/shop_filters.php'; ?>
            </div>
        </div>

        <!-- Products Section -->
        <div class="col-lg-9">
            <!-- Shop Toolbar -->
            <div class="shop-toolbar bg-light rounded p-3 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted">
                                <?php 
                                $start = $offset + 1;
                                $end = min($offset + $products_per_page, $total_products);
                                echo "Showing $start-$end of " . number_format($total_products) . " results";
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <!-- Preserve existing filters -->
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if ($key !== 'sort' && $key !== 'page'): ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <label for="sort" class="form-label mb-0 text-nowrap">Sort by:</label>
                            <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="default" <?php echo $sort_key === 'default' ? 'selected' : ''; ?>>Default</option>
                                <option value="newest" <?php echo $sort_key === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="name_asc" <?php echo $sort_key === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="name_desc" <?php echo $sort_key === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                                <option value="price_asc" <?php echo $sort_key === 'price_asc' ? 'selected' : ''; ?>>Price Low to High</option>
                                <option value="price_desc" <?php echo $sort_key === 'price_desc' ? 'selected' : ''; ?>>Price High to Low</option>
                                <option value="rating" <?php echo $sort_key === 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                                <option value="bestseller" <?php echo $sort_key === 'bestseller' ? 'selected' : ''; ?>>Best Sellers</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if ($result_products && $result_products->num_rows > 0): ?>
                <div class="products-grid">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                        <?php while($book = $result_products->fetch_assoc()): ?>
                            <?php render_book_card($book); ?>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-5 d-flex justify-content-center" aria-label="Products pagination">
                    <ul class="pagination pagination-lg">
                        <!-- Previous Page -->
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        // Show first page if not in range
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=1">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Current range -->
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Show last page if not in range -->
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Next Page -->
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Products Found -->
                <div class="no-products text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-search display-1 text-muted"></i>
                    </div>
                    <h3 class="h4 mb-3">No books found</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($search)): ?>
                            We couldn't find any books matching "<?php echo htmlspecialchars($search); ?>".
                        <?php else: ?>
                            No books match your current filters.
                        <?php endif; ?>
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                        <a href="/bookshelf/shop.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left me-2"></i>Browse All Books
                        </a>
                        <?php if (!empty($active_filters)): ?>
                            <a href="/bookshelf/shop.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mobile Filter Offcanvas -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterSidebar" aria-labelledby="filterSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filterSidebarLabel">
            <i class="bi bi-funnel me-2"></i>Filter Books
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?php include 'includes/shop_filters.php'; ?>
    </div>
</div>

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

<style>
/* Shop Page Styles */
.filter-sidebar {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.filter-widget {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.filter-widget:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.filter-widget h6 {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.form-check {
    margin-bottom: 0.75rem;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-check-label {
    font-size: 0.9rem;
    cursor: pointer;
}

.products-grid .card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.products-grid .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.shop-toolbar {
    border: 1px solid #e9ecef;
}

.pagination .page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: none;
    color: #667eea;
    font-weight: 500;
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: #667eea;
}

.pagination .page-link:hover {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
}

.active-filters .badge {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.no-products {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 3rem 2rem;
}

.price-range-slider {
    margin: 1rem 0;
}

.price-inputs {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.price-inputs input {
    width: 80px;
    font-size: 0.875rem;
}

@media (max-width: 991.98px) {
    .filter-sidebar {
        position: relative !important;
        top: auto !important;
    }
}

@media (max-width: 767.98px) {
    .shop-toolbar .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .products-grid .row {
        --bs-gutter-x: 1rem;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Price range slider functionality
    const priceRange = document.getElementById('priceRange');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    if (priceRange && minPriceInput && maxPriceInput) {
        // Initialize slider based on current values
        updatePriceRange();
        
        // Update when inputs change
        minPriceInput.addEventListener('input', updatePriceRange);
        maxPriceInput.addEventListener('input', updatePriceRange);
    }
    
    function updatePriceRange() {
        const minVal = parseFloat(minPriceInput.value) || 0;
        const maxVal = parseFloat(maxPriceInput.value) || 100;
        
        if (minVal >= maxVal) {
            maxPriceInput.value = minVal + 1;
        }
    }
    
    // Professional Quick view functionality
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
        
        // Show enhanced loading state
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
        
        // Load product data with enhanced error handling
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
                    
                    // Add smooth fade-in animation
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
                if (typeof showNotification === 'function') {
                    showNotification('Product added to cart successfully!', 'success');
                }
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
                    if (typeof showNotification === 'function') {
                        showNotification('Added to wishlist!', 'success');
                    }
                    if (button) {
                        button.innerHTML = '<i class="bi bi-heart-fill"></i>';
                        button.classList.add('btn-danger');
                        button.classList.remove('btn-outline-danger');
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Failed to add to wishlist', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Wishlist error:', error);
                if (typeof showNotification === 'function') {
                    showNotification('Failed to add to wishlist', 'error');
                }
            });
        }
    };
    
    // Filter form auto-submit
    document.querySelectorAll('.filter-form input[type="checkbox"], .filter-form input[type="radio"]').forEach(input => {
        input.addEventListener('change', function() {
            // Small delay to allow for multiple quick selections
            clearTimeout(window.filterTimeout);
            window.filterTimeout = setTimeout(() => {
                this.closest('form').submit();
            }, 300);
        });
    });
    
    // Smooth scroll to top when pagination changes
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page') && urlParams.get('page') !== '1') {
        setTimeout(() => {
            window.scrollTo({
                top: document.querySelector('.shop-header').offsetTop - 100,
                behavior: 'smooth'
            });
        }, 100);
    }
});
</script>

<?php include 'includes/footer.php'; ?>