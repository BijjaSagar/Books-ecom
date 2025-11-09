<?php
// Enhanced product-details.php with modern design and better functionality
session_start();

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';

// --- SECURE DATA FETCHING ---
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    $page_title_override = "Invalid Product - Bookory";
    include 'includes/header.php';
    echo "<div class='container my-5'><div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>Invalid product ID.</div></div>";
    include 'includes/footer.php';
    exit();
}

// Get product details with category information
// Handle both MySQLi and SQLite3 prepared statements
if ($conn instanceof SQLite3) {
    // SQLite3 approach
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND (p.status = 'active' OR p.status IS NULL)
    ");
    $stmt->bindValue(1, $product_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $book = $result->fetchArray(SQLITE3_ASSOC);
} else {
    // MySQLi approach
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND (p.status = 'active' OR p.status IS NULL)
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
}

if (!$book) {
    $page_title_override = "Product Not Found - Bookory";
    include 'includes/header.php';
    echo "<div class='container my-5'><div class='alert alert-danger'><i class='bi bi-search me-2'></i>Product not found or no longer available.</div></div>";
    include 'includes/footer.php';
    exit();
}

// Get related products (same category)
$related_products = [];
if (!empty($book['category_id'])) {
    if ($conn instanceof SQLite3) {
        // SQLite3 approach
        $related_stmt = $conn->prepare("
            SELECT * FROM products 
            WHERE category_id = ? AND id != ? AND (status = 'active' OR status IS NULL)
            ORDER BY RANDOM() 
            LIMIT 4
        ");
        $related_stmt->bindValue(1, $book['category_id'], SQLITE3_INTEGER);
        $related_stmt->bindValue(2, $product_id, SQLITE3_INTEGER);
        $related_result = $related_stmt->execute();
        while ($row = $related_result->fetchArray(SQLITE3_ASSOC)) {
            $related_products[] = $row;
        }
    } else {
        // MySQLi approach
        $related_stmt = $conn->prepare("
            SELECT * FROM products 
            WHERE category_id = ? AND id != ? AND (status = 'active' OR status IS NULL)
            ORDER BY RAND() 
            LIMIT 4
        ");
        $related_stmt->bind_param("ii", $book['category_id'], $product_id);
        $related_stmt->execute();
        $related_result = $related_stmt->get_result();
        $related_products = $related_result->fetch_all(MYSQLI_ASSOC);
        $related_stmt->close();
    }
}

// Calculate pricing information
$original_price = !empty($book['original_price']) && $book['original_price'] > $book['price'] ? $book['original_price'] : null;
$discount_percentage = 0;
if ($original_price) {
    $discount_percentage = round((($original_price - $book['price']) / $original_price) * 100);
}

// Generate star rating
$rating = $book['rating'] ?? 0;
$reviews_count = $book['reviews_count'] ?? 0;
$stars_html = '';
for ($i = 1; $i <= 5; $i++) {
    if ($i <= $rating) {
        $stars_html .= '<i class="bi bi-star-fill text-warning"></i>';
    } elseif ($i - 0.5 <= $rating) {
        $stars_html .= '<i class="bi bi-star-half text-warning"></i>';
    } else {
        $stars_html .= '<i class="bi bi-star text-muted"></i>';
    }
}

// Stock status
$stock_quantity = $book['stock_quantity'] ?? 0;
$is_in_stock = $stock_quantity > 0;
$low_stock = $stock_quantity > 0 && $stock_quantity <= 5;

// Check if book has preview content
$has_preview = !empty($book['preview_content']) || !empty($book['preview_file']);

include 'includes/header.php';
?>

<style>
.product-details-page {
    font-family: 'Inter', sans-serif;
}

.product-image-gallery {
    position: relative;
    background: #f8f9fa;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
}

.product-image-gallery img {
    max-height: 500px;
    width: auto;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: transform 0.3s ease;
}

.product-image-gallery img:hover {
    transform: scale(1.02);
}

.stock-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-badge.in-stock {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: white;
}

.stock-badge.out-of-stock {
    background: linear-gradient(135deg, #ef4444, #f87171);
    color: white;
}

.stock-badge.low-stock {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: white;
}

.product-title {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 1rem 0;
    color: #1f2937;
}

.author-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.author-link:hover {
    color: #5a67d8;
}

.price-section {
    margin: 2rem 0;
}

.current-price {
    font-size: 2.5rem;
    font-weight: 700;
    color: #667eea;
    margin-right: 1rem;
}

.original-price {
    font-size: 1.5rem;
    color: #9ca3af;
    text-decoration: line-through;
}

.discount-badge {
    background: linear-gradient(135deg, #ef4444, #f87171);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-left: 1rem;
}

.product-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #6b7280;
    margin: 1.5rem 0;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    width: 140px;
}

.quantity-btn {
    background: #f3f4f6;
    border: none;
    padding: 0.75rem 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
}

.quantity-btn:hover {
    background: #e5e7eb;
}

.quantity-btn:disabled {
    background: #f9fafb;
    cursor: not-allowed;
}

.quantity-selector input {
    border: none;
    text-align: center;
    width: 60px;
    font-weight: 600;
    background: white;
}

.add-to-cart-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.add-to-cart-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.add-to-cart-btn:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.wishlist-btn {
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.wishlist-btn:hover {
    color: #667eea;
}

.product-tabs .nav-link {
    border: none;
    padding: 1rem 2rem;
    font-weight: 600;
    color: #6b7280;
    border-bottom: 3px solid transparent;
}

.product-tabs .nav-link.active {
    color: #667eea;
    border-bottom: 3px solid #667eea;
    background: transparent;
}

.preview-content {
    max-height: 400px;
    overflow-y: auto;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-family: 'Georgia', serif;
    line-height: 1.8;
}

.preview-content h4 {
    color: #1f2937;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.preview-content p {
    margin-bottom: 1rem;
    text-align: justify;
}

.read-preview-btn {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    color: white;
}

.read-preview-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);
}

.read-preview-btn i {
    margin-right: 0.5rem;
}

.modal-preview-content {
    max-height: 70vh;
    overflow-y: auto;
    padding: 1rem;
    font-family: 'Georgia', serif;
    line-height: 1.8;
}

.modal-preview-content h4 {
    color: #1f2937;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.modal-preview-content p {
    margin-bottom: 1rem;
    text-align: justify;
}
</style>

<div class="container product-details-page my-5">
    <div class="row g-5">
        <!-- Left Column: Product Images -->
        <div class="col-lg-6">
            <div class="product-image-gallery">
                <?php if (!empty($book['cover_image']) && file_exists("public/images/" . $book['cover_image'])): ?>
                    <img src="/bookshelf/public/images/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                         class="img-fluid">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 500px; border-radius: 12px;">
                        <i class="bi bi-book" style="font-size: 5rem; color: #ddd;"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Discount Badge -->
                <?php if ($discount_percentage > 0): ?>
                <div class="position-absolute top-0 end-0 m-3">
                    <span class="discount-badge">-<?php echo $discount_percentage; ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Product Info -->
        <div class="col-lg-6">
            <!-- Stock Status -->
            <div class="mb-3">
                <?php if ($is_in_stock): ?>
                    <?php if ($low_stock): ?>
                        <span class="stock-badge low-stock">
                            <i class="bi bi-exclamation-triangle me-1"></i>Low Stock (<?php echo $stock_quantity; ?> left)
                        </span>
                    <?php else: ?>
                        <span class="stock-badge in-stock">
                            <i class="bi bi-check-circle me-1"></i>In Stock
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="stock-badge out-of-stock">
                        <i class="bi bi-x-circle me-1"></i>Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Product Title -->
            <h1 class="product-title"><?php echo htmlspecialchars($book['title']); ?></h1>
            
            <!-- Author and Rating -->
            <div class="d-flex align-items-center mb-3">
                <span class="text-muted me-3">by 
                    <a href="#" class="author-link"><?php echo htmlspecialchars($book['author'] ?? 'Unknown Author'); ?></a>
                </span>
                <?php if ($rating > 0): ?>
                <div class="me-3">
                    <span class="stars"><?php echo $stars_html; ?></span>
                    <?php if ($reviews_count > 0): ?>
                        <small class="text-muted ms-2">(<?php echo $reviews_count; ?> reviews)</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Price Section -->
            <div class="price-section">
                <span class="current-price">₹<?php echo number_format($book['price'], 2); ?></span>
                <?php if ($original_price): ?>
                    <span class="original-price">₹<?php echo number_format($original_price, 2); ?></span>
                <?php endif; ?>
            </div>

            <!-- Product Description -->
            <div class="product-description">
                <?php 
                $description = $book['description'] ?? 'No description available.';
                $short_description = strlen($description) > 300 ? substr($description, 0, 300) . '...' : $description;
                echo nl2br(htmlspecialchars($short_description)); 
                ?>
            </div>

            <!-- Book Preview Button -->
            <?php if ($has_preview): ?>
            <div class="mb-4">
                <button class="read-preview-btn" data-bs-toggle="modal" data-bs-target="#previewModal">
                    <i class="bi bi-book-half"></i>Read Sample Chapter
                </button>
            </div>
            <?php endif; ?>

            <!-- Product Meta Information -->
            <div class="product-meta">
                <div class="row">
                    <?php if (!empty($book['category_name'])): ?>
                    <div class="col-md-6 mb-2">
                        <h6>Category</h6>
                        <p><?php echo htmlspecialchars($book['category_name']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($book['isbn'])): ?>
                    <div class="col-md-6 mb-2">
                        <h6>ISBN</h6>
                        <p><?php echo htmlspecialchars($book['isbn']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($book['publisher'])): ?>
                    <div class="col-md-6 mb-2">
                        <h6>Publisher</h6>
                        <p><?php echo htmlspecialchars($book['publisher']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($book['pages'])): ?>
                    <div class="col-md-6 mb-2">
                        <h6>Pages</h6>
                        <p><?php echo htmlspecialchars($book['pages']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add to Cart Form -->
            <form action="/bookshelf/cart.php" method="POST" class="mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $book['id']; ?>">
                
                <div class="row align-items-end g-3">
                    <div class="col-auto">
                        <label class="form-label fw-semibold">Quantity:</label>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" onclick="decreaseQuantity()">-</button>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity" 
                                   value="1" 
                                   min="1" 
                                   max="<?php echo max(1, $stock_quantity); ?>" 
                                   readonly>
                            <button type="button" class="quantity-btn" onclick="increaseQuantity()">+</button>
                        </div>
                    </div>
                    
                    <div class="col">
                        <button type="submit" 
                                class="btn add-to-cart-btn w-100" 
                                <?php echo !$is_in_stock ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus me-2"></i>
                            <?php echo $is_in_stock ? 'Add to Cart' : 'Out of Stock'; ?>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Additional Actions -->
            <div class="d-flex gap-3">
                <a href="#" class="wishlist-btn">
                    <i class="bi bi-heart me-2"></i>Add to Wishlist
                </a>
                <a href="#" class="wishlist-btn">
                    <i class="bi bi-share me-2"></i>Share
                </a>
            </div>
        </div>
    </div>

    <!-- Product Details Tabs -->
    <div class="mt-5 pt-4">
        <ul class="nav nav-tabs product-tabs justify-content-center" id="productTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-pane" type="button">
                    Description
                </button>
            </li>
            <?php if ($has_preview): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview-pane" type="button">
                    Preview
                </button>
            </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button">
                    Additional Information
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button">
                    Reviews<?php echo $reviews_count > 0 ? " ($reviews_count)" : ''; ?>
                </button>
            </li>
        </ul>
        
        <div class="tab-content bg-white p-4" id="productTabContent">
            <div class="tab-pane fade show active" id="description-pane" role="tabpanel">
                <h5 class="fw-bold mb-3">Full Description</h5>
                <div class="text-muted lh-lg">
                    <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No detailed description available.')); ?>
                </div>
            </div>
            
            <?php if ($has_preview): ?>
            <div class="tab-pane fade" id="preview-pane" role="tabpanel">
                <h5 class="fw-bold mb-3">Book Preview</h5>
                <div class="preview-content">
                    <?php if (!empty($book['preview_content'])): ?>
                        <?php echo $book['preview_content']; ?>
                    <?php elseif (!empty($book['preview_file']) && file_exists("public/previews/" . $book['preview_file'])): ?>
                        <?php echo file_get_contents("public/previews/" . $book['preview_file']); ?>
                    <?php else: ?>
                        <p>No preview content available for this book.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <button class="read-preview-btn" data-bs-toggle="modal" data-bs-target="#previewModal">
                        <i class="bi bi-arrows-fullscreen"></i>Read Full Preview
                    </button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="tab-pane fade" id="info-pane" role="tabpanel">
                <h5 class="fw-bold mb-3">Additional Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td class="fw-medium">Author:</td>
                                <td><?php echo htmlspecialchars($book['author'] ?? 'Unknown'); ?></td>
                            </tr>
                            <?php if (!empty($book['isbn'])): ?>
                            <tr>
                                <td class="fw-medium">ISBN:</td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($book['publisher'])): ?>
                            <tr>
                                <td class="fw-medium">Publisher:</td>
                                <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <?php if (!empty($book['pages'])): ?>
                            <tr>
                                <td class="fw-medium">Pages:</td>
                                <td><?php echo htmlspecialchars($book['pages']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($book['language'])): ?>
                            <tr>
                                <td class="fw-medium">Language:</td>
                                <td><?php echo htmlspecialchars($book['language']); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="fw-medium">Format:</td>
                                <td><?php echo htmlspecialchars($book['format'] ?? 'Paperback'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="reviews-pane" role="tabpanel">
                <h5 class="fw-bold mb-3">Customer Reviews</h5>
                <div class="text-center py-5">
                    <i class="bi bi-chat-text display-1 text-muted"></i>
                    <h6 class="mt-3 text-muted">No reviews yet</h6>
                    <p class="text-muted">Be the first to review this book!</p>
                    <button class="btn btn-outline-primary">Write a Review</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <div class="related-products">
        <h3>You might also like</h3>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($related_products as $related_book): ?>
                <?php render_book_card($related_book); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Preview Modal -->
<?php if ($has_preview): ?>
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="bi bi-book-half me-2"></i>Preview: <?php echo htmlspecialchars($book['title']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-preview-content">
                    <?php if (!empty($book['preview_content'])): ?>
                        <?php echo $book['preview_content']; ?>
                    <?php elseif (!empty($book['preview_file']) && file_exists("public/previews/" . $book['preview_file'])): ?>
                        <?php echo file_get_contents("public/previews/" . $book['preview_file']); ?>
                    <?php else: ?>
                        <p>No preview content available for this book.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="/bookshelf/cart.php?action=add&product_id=<?php echo $book['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Quantity selector functionality
function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max'));
    const current = parseInt(input.value);
    
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    const min = parseInt(input.getAttribute('min'));
    const current = parseInt(input.value);
    
    if (current > min) {
        input.value = current - 1;
    }
}

// Add to cart with AJAX (optional enhancement)
document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Adding...';
    submitBtn.disabled = true;
    
    // Re-enable after a short delay (or implement actual AJAX)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 1000);
});

// Image zoom on hover (optional)
const productImage = document.querySelector('.product-image-gallery img');
if (productImage) {
    productImage.addEventListener('click', function() {
        // Implement lightbox or zoom functionality here
        console.log('Image clicked - implement zoom/lightbox');
    });
}
</script>

<?php include 'includes/footer.php'; ?>