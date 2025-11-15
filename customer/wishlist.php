<?php
/**
 * Customer Wishlist Page
 * View and manage saved items
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/WishlistManager.php';

$wishlist_manager = new WishlistManager($conn);

// Handle wishlist actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove') {
        $product_id = intval($_POST['product_id']);
        $result = $wishlist_manager->removeFromWishlist($_SESSION['user_id'], $product_id);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($_POST['action'] === 'clear') {
        $result = $wishlist_manager->clearWishlist($_SESSION['user_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

// Get customer's wishlist
$wishlist = $wishlist_manager->getWishlist($_SESSION['user_id']);
$wishlist_count = count($wishlist);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --border-color: #e5e7eb;
            --text-primary: #374151;
            --text-secondary: #6b7280;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: #f3f4f6;
        }

        .navbar {
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
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

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .wishlist-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .wishlist-count {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .clear-btn {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .clear-btn:hover {
            background: #dc2626;
        }

        .clear-btn:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            background: #f3f4f6;
            padding: 40px 20px;
            text-align: center;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image-icon {
            font-size: 4rem;
            color: #9ca3af;
        }

        .product-content {
            padding: 20px;
        }

        .product-title {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 8px;
            color: var(--text-primary);
            min-height: 40px;
        }

        .product-author {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 12px;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .product-rating {
            color: #fbbf24;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .product-stock {
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .stock-in {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-add-cart {
            background: var(--primary);
            color: white;
        }

        .btn-add-cart:hover {
            background: var(--primary-dark);
        }

        .btn-remove {
            background: var(--danger);
            color: white;
        }

        .btn-remove:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 24px;
        }

        .continue-btn {
            background: var(--primary);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }

        .continue-btn:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .wishlist-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }

            .product-content {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="color: var(--primary); font-weight: 700;">📚 Books</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="wishlist.php">❤️ Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link" href="edit-profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>❤️ My Wishlist</h1>
            <p>Save your favorite books for later</p>
        </div>
    </div>

    <!-- Content -->
    <div class="container mb-5">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <?php if ($wishlist_count > 0): ?>
                <div class="wishlist-controls">
                    <div class="wishlist-count">
                        ❤️ <?php echo $wishlist_count; ?> Item<?php echo $wishlist_count !== 1 ? 's' : ''; ?> in Wishlist
                    </div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="clear-btn" onclick="return confirm('Clear entire wishlist?');">
                            🗑️ Clear All
                        </button>
                    </form>
                </div>

                <div class="wishlist-grid">
                    <?php foreach ($wishlist as $item): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <div class="product-image-icon">📖</div>
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><?php echo htmlspecialchars(substr($item['title'], 0, 50)); ?></h3>
                                <div class="product-author">by <?php echo htmlspecialchars($item['author']); ?></div>

                                <?php if ($item['rating'] > 0): ?>
                                    <div class="product-rating">
                                        ★ <?php echo number_format($item['rating'], 1); ?> (<?php echo $item['review_count']; ?> reviews)
                                    </div>
                                <?php endif; ?>

                                <div class="product-price">₹<?php echo number_format($item['price'], 0); ?></div>

                                <div class="product-stock <?php echo $item['stock_quantity'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                                    <?php echo $item['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </div>

                                <div class="product-actions">
                                    <button class="action-btn btn-add-cart" <?php echo $item['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                        🛒 Cart
                                    </button>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="action-btn btn-remove" style="width: 100%;">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💔</div>
                    <h2>Your Wishlist is Empty</h2>
                    <p>Start adding your favorite books to your wishlist!</p>
                    <a href="index.php" class="continue-btn">Browse Books →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
