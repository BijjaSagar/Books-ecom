<?php
/**
 * Product Details Page
 * Display detailed product information, reviews, and ratings
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/ReviewManager.php';

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get product details
$product_stmt = $conn->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$product_stmt->bind_param('i', $product_id);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();
$product_stmt->close();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Get review manager instance
$review_manager = new ReviewManager($conn);
$reviews = $review_manager->getProductReviews($product_id);
$rating_stats = $review_manager->getProductRatingStats($product_id);

// Check if user purchased this product
$can_review = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer') {
    $purchase_check = $conn->prepare("
        SELECT COUNT(*) as count FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.product_id = ? AND o.user_id = ? AND o.order_status IN ('completed', 'delivered')
    ");
    $purchase_check->bind_param('ii', $product_id, $_SESSION['user_id']);
    $purchase_check->execute();
    $can_review = $purchase_check->get_result()->fetch_assoc()['count'] > 0;
    $purchase_check->close();
}

// Check if already reviewed
$has_reviewed = false;
if ($can_review) {
    $review_check = $conn->prepare("
        SELECT COUNT(*) as count FROM product_reviews
        WHERE product_id = ? AND user_id = ?
    ");
    $review_check->bind_param('ii', $product_id, $_SESSION['user_id']);
    $review_check->execute();
    $has_reviewed = $review_check->get_result()->fetch_assoc()['count'] > 0;
    $review_check->close();
}

// Handle review submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = 'Please log in to submit a review!';
        $message_type = 'error';
    } elseif (!$can_review) {
        $message = 'You must purchase this product to review it!';
        $message_type = 'error';
    } elseif ($has_reviewed) {
        $message = 'You have already reviewed this product!';
        $message_type = 'error';
    } else {
        $result = $review_manager->submitReview(
            $_SESSION['user_id'],
            $product_id,
            intval($_POST['rating']),
            trim($_POST['title']),
            trim($_POST['comment'])
        );
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            $has_reviewed = true;
            // Refresh reviews
            $reviews = $review_manager->getProductReviews($product_id);
            $rating_stats = $review_manager->getProductRatingStats($product_id);
        }
    }
}

// Get related products
$related_stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? 
    LIMIT 4
");
$related_stmt->bind_param('ii', $product['category_id'], $product_id);
$related_stmt->execute();
$related_products = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$related_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --border-color: #e5e7eb;
            --text-primary: #374151;
            --text-secondary: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            background: #f9fafb;
            color: var(--text-primary);
        }

        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .product-hero {
            background: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .product-image {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            max-width: 100%;
            height: auto;
        }

        .product-info {
            padding: 20px 0;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .product-author {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .rating-section {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .rating-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stars {
            font-size: 1.5rem;
            color: #fbbf24;
        }

        .review-count {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .price-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 24px;
            background: #f3f4f6;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .price-item {
            text-align: center;
        }

        .price-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .price-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 12px;
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .add-to-cart-btn:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .wishlist-btn {
            width: 100%;
            padding: 12px;
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .wishlist-btn:hover {
            background: var(--primary);
            color: white;
        }

        .product-details-tab {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 40px;
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            border-bottom: 3px solid transparent;
            padding: 12px 24px;
            font-weight: 600;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: none;
        }

        .description {
            line-height: 1.8;
            color: var(--text-primary);
        }

        .reviews-section {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 40px;
        }

        .reviews-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 2px solid var(--border-color);
        }

        .rating-stats {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rating-stars {
            width: 60px;
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .rating-bar-container {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .rating-bar-fill {
            height: 100%;
            background: var(--primary);
        }

        .rating-count {
            width: 40px;
            text-align: right;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .review-form {
            background: #f9fafb;
            padding: 24px;
            border-radius: 8px;
        }

        .review-form h4 {
            color: var(--primary);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            display: block;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .reviews-list {
            margin-top: 32px;
        }

        .review-item {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .reviewer-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .review-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .review-rating {
            color: #fbbf24;
            margin-bottom: 8px;
        }

        .review-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .review-comment {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .verified-badge {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }

        .related-products {
            margin-top: 40px;
        }

        .related-products h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .product-card-image {
            background: #f3f4f6;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-card-content {
            padding: 16px;
        }

        .product-card-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            min-height: 45px;
        }

        .product-card-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .product-title {
                font-size: 1.5rem;
            }

            .reviews-header {
                grid-template-columns: 1fr;
            }

            .price-section {
                grid-template-columns: 1fr;
            }

            .product-image {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="color: var(--primary); font-weight: 700;">📚 Books</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="edit-profile.php">Profile</a></li>
                        <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Product Details -->
    <div class="product-hero">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="product-image">
                        <div style="text-align: center;">
                            <div style="font-size: 4rem; color: #9ca3af;">📖</div>
                            <p style="color: var(--text-secondary); margin-top: 12px;">Book Cover</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="product-info">
                        <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                        <div class="product-author">by <?php echo htmlspecialchars($product['author']); ?></div>
                        
                        <div class="rating-section">
                            <div class="rating-display">
                                ★ <?php echo number_format($product['rating'] ?? 0, 1); ?>
                            </div>
                            <div>
                                <div class="review-count">
                                    <?php echo ($product['review_count'] ?? 0); ?> Reviews
                                </div>
                                <div class="review-count">
                                    Category: <?php echo htmlspecialchars($product['category_name']); ?>
                                </div>
                            </div>
                        </div>

                        <div class="price-section">
                            <div class="price-item">
                                <div class="price-label">Price</div>
                                <div class="price-value">₹<?php echo number_format($product['price'], 0); ?></div>
                            </div>
                            <div class="price-item">
                                <div class="price-label">Stock</div>
                                <div class="price-value" style="color: <?php echo $product['stock_quantity'] > 0 ? '#10b981' : '#ef4444'; ?>">
                                    <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </div>
                            </div>
                        </div>

                        <button class="add-to-cart-btn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                            🛒 Add to Cart
                        </button>
                        <button class="wishlist-btn">❤️ Add to Wishlist</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Tabs -->
    <div class="container">
        <div class="product-details-tab">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button">Description</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button">Details</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <div class="description mt-4">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available')); ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="specs" role="tabpanel">
                    <div class="mt-4">
                        <table class="table">
                            <tr>
                                <td style="width: 30%;"><strong>Product ID</strong></td>
                                <td>#<?php echo $product['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Author</strong></td>
                                <td><?php echo htmlspecialchars($product['author']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category</strong></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Price</strong></td>
                                <td>₹<?php echo number_format($product['price'], 0); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Stock Available</strong></td>
                                <td><?php echo $product['stock_quantity']; ?> units</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 style="margin-bottom: 32px; color: var(--text-primary);">Customer Reviews & Ratings</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="reviews-header">
                <div>
                    <div style="font-size: 3rem; font-weight: 700; color: var(--primary); margin-bottom: 12px;">
                        ★ <?php echo number_format($rating_stats['average_rating'] ?? 0, 1); ?>
                    </div>
                    <div style="color: var(--text-secondary);">
                        Based on <?php echo ($rating_stats['total_reviews'] ?? 0); ?> reviews
                    </div>
                </div>

                <div class="rating-stats">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-bar">
                            <div class="rating-stars">
                                <?php echo str_repeat('★', $i); ?>
                            </div>
                            <div class="rating-bar-container">
                                <div class="rating-bar-fill" style="width: <?php 
                                    $count = $rating_stats['rating_distribution'][$i] ?? 0;
                                    $total = $rating_stats['total_reviews'] ?? 1;
                                    echo ($count / max($total, 1)) * 100;
                                ?>%"></div>
                            </div>
                            <div class="rating-count">
                                <?php echo $rating_stats['rating_distribution'][$i] ?? 0; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Review Form -->
            <?php if ($can_review && !$has_reviewed): ?>
                <div class="review-form">
                    <h4>✏️ Write a Review</h4>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Rating *</label>
                            <select name="rating" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                                <option value="">Select a rating</option>
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Very Good</option>
                                <option value="3">⭐⭐⭐ Good</option>
                                <option value="2">⭐⭐ Fair</option>
                                <option value="1">⭐ Poor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Review Title *</label>
                            <input type="text" name="title" required placeholder="Summarize your experience">
                        </div>
                        <div class="form-group">
                            <label>Your Review *</label>
                            <textarea name="comment" required placeholder="Share your thoughts about this book..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="submit-btn">Submit Review</button>
                    </form>
                </div>
            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer' && !$can_review): ?>
                <div style="background: #fef3c7; padding: 16px; border-radius: 8px; border-left: 4px solid var(--warning); margin-bottom: 24px;">
                    <strong style="color: #92400e;">💡 Note:</strong> You must purchase this product to write a review.
                </div>
            <?php endif; ?>

            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if (!empty($reviews) && count($reviews) > 0): ?>
                    <h4 style="margin-bottom: 24px; color: var(--text-primary);">All Reviews (<?php echo count($reviews); ?>)</h4>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <div class="reviewer-name">
                                        <?php echo htmlspecialchars($review['customer_name']); ?>
                                        <?php if ($review['verified_purchase']): ?>
                                            <span class="verified-badge">✓ Verified Purchase</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="review-date">
                                        <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php echo str_repeat('★', $review['rating']); ?><?php echo str_repeat('☆', 5 - $review['rating']); ?>
                            </div>
                            <div class="review-title"><?php echo htmlspecialchars($review['title']); ?></div>
                            <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <p style="font-size: 1.1rem;">No reviews yet</p>
                        <p>Be the first to review this product!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h3>Related Books</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="product-card">
                                <div class="product-card-image">
                                    <div style="text-align: center; color: #9ca3af;">
                                        <div style="font-size: 3rem;">📖</div>
                                    </div>
                                </div>
                                <div class="product-card-content">
                                    <div class="product-card-title"><?php echo htmlspecialchars(substr($related['title'], 0, 50)); ?></div>
                                    <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 12px;">
                                        <?php echo htmlspecialchars($related['author']); ?>
                                    </div>
                                    <div class="product-card-price">₹<?php echo number_format($related['price'], 0); ?></div>
                                    <a href="product-details.php?id=<?php echo $related['id']; ?>" style="display: block; margin-top: 12px; color: var(--primary); text-decoration: none; font-weight: 600;">
                                        View Details →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
