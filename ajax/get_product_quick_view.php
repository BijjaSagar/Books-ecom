<?php
// Create this file as: ajax/get_product_quick_view.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Get product details with category
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Calculate stock status
    $stock_quantity = $product['stock_quantity'] ?? 0;
    $in_stock = $stock_quantity > 0;
    
    // Generate the HTML content for quick view
    ob_start();
    ?>
    <div class="quick-view-content">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="quick-view-image">
                    <?php 
                    $image_url = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 
                                 (!empty($product['cover_image']) ? htmlspecialchars($product['cover_image']) : 
                                 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=400&h=600&fit=crop&q=80');
                    ?>
                    <img src="<?php echo $image_url; ?>" 
                         class="img-fluid" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         loading="lazy">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="product-details h-100 d-flex flex-column">
                    
                    <!-- Category Badge -->
                    <?php if (!empty($product['category_name'])): ?>
                        <div class="quick-view-badge">
                            <i class="bi bi-tag-fill"></i>
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Title -->
                    <h2 class="quick-view-title"><?php echo htmlspecialchars($product['title']); ?></h2>
                    
                    <!-- Author -->
                    <div class="quick-view-author">
                        <i class="bi bi-person-fill"></i>
                        <span>by <?php echo htmlspecialchars($product['author'] ?? 'Unknown Author'); ?></span>
                    </div>
                    
                    <!-- Rating -->
                    <?php if (isset($product['rating']) && $product['rating'] > 0): ?>
                        <div class="quick-view-rating">
                            <div class="quick-view-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i <= $product['rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="quick-view-rating-text">
                                <strong><?php echo number_format($product['rating'], 1); ?></strong> out of 5
                                <?php if (isset($product['reviews_count']) && $product['reviews_count'] > 0): ?>
                                    (<?php echo number_format($product['reviews_count']); ?> reviews)
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Price -->
                    <div class="quick-view-price">
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="quick-view-current-price">
                                $<?php echo number_format($product['price'], 2); ?>
                            </span>
                            <?php if (isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                <span class="quick-view-original-price">
                                    $<?php echo number_format($product['original_price'], 2); ?>
                                </span>
                                <span class="quick-view-discount">
                                    <?php echo round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>% OFF
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Stock Status -->
                    <div class="quick-view-stock <?php echo !$in_stock ? 'out-of-stock' : ''; ?>">
                        <i class="bi bi-circle-fill <?php echo $in_stock ? 'text-success' : 'text-danger'; ?>"></i>
                        <span class="fw-semibold <?php echo $in_stock ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $in_stock ? 'In Stock' : 'Out of Stock'; ?>
                        </span>
                        <?php if ($in_stock && $stock_quantity <= 5): ?>
                            <span class="text-warning fw-medium">(Only <?php echo $stock_quantity; ?> left!)</span>
                        <?php elseif ($in_stock): ?>
                            <span class="text-muted">(<?php echo $stock_quantity; ?> available)</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <?php if (!empty($product['description'])): ?>
                        <div class="quick-view-description">
                            <h6>
                                <i class="bi bi-card-text"></i>
                                Description
                            </h6>
                            <p>
                                <?php 
                                $description = htmlspecialchars($product['description']);
                                echo strlen($description) > 250 ? substr($description, 0, 250) . '...' : $description;
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Additional Product Info -->
                    <?php if (!empty($product['isbn']) || !empty($product['pages']) || !empty($product['publication_year'])): ?>
                        <div class="quick-view-specs mb-4">
                            <div class="row g-3 text-sm">
                                <?php if (!empty($product['isbn'])): ?>
                                    <div class="col-6">
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($product['isbn']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($product['pages'])): ?>
                                    <div class="col-6">
                                        <strong>Pages:</strong> <?php echo htmlspecialchars($product['pages']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($product['publication_year'])): ?>
                                    <div class="col-6">
                                        <strong>Year:</strong> <?php echo htmlspecialchars($product['publication_year']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="quick-view-actions mt-auto">
                        <?php if ($in_stock): ?>
                            <div class="d-flex gap-3 mb-4 align-items-center flex-wrap">
                                <div class="quantity-control input-group">
                                    <button class="btn" type="button" onclick="updateQuantity(-1)">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <input type="number" 
                                           class="form-control" 
                                           id="quickViewQuantity" 
                                           value="1" 
                                           min="1" 
                                           max="<?php echo $stock_quantity; ?>"
                                           readonly>
                                    <button class="btn" type="button" onclick="updateQuantity(1)">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <button class="btn quick-view-add-cart flex-fill" 
                                        onclick="addToCartFromQuickView(<?php echo $product['id']; ?>)">
                                    <i class="bi bi-cart-plus me-2"></i>
                                    Add to Cart
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <button class="btn quick-view-out-of-stock w-100" disabled>
                                    <i class="bi bi-x-circle me-2"></i>
                                    Currently Out of Stock
                                </button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-3">
                            <a href="/bookshelf/product-details.php?id=<?php echo $product['id']; ?>" 
                               class="btn quick-view-view-details flex-fill">
                                <i class="bi bi-eye"></i>
                                View Full Details
                            </a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn quick-view-wishlist" 
                                        onclick="addToWishlistFromQuickView(<?php echo $product['id']; ?>)"
                                        title="Add to Wishlist">
                                    <i class="bi bi-heart"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Trust Badges -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row g-2 text-center text-muted small">
                                <div class="col-4">
                                    <i class="bi bi-shield-check text-success"></i>
                                    <div>Secure Payment</div>
                                </div>
                                <div class="col-4">
                                    <i class="bi bi-truck text-primary"></i>
                                    <div>Fast Shipping</div>
                                </div>
                                <div class="col-4">
                                    <i class="bi bi-arrow-clockwise text-info"></i>
                                    <div>Easy Returns</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'product' => [
            'id' => $product['id'],
            'title' => $product['title'],
            'price' => $product['price'],
            'in_stock' => $in_stock,
            'stock_quantity' => $stock_quantity
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Quick view error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading the product']);
}
?>