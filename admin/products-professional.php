<?php
/**
 * Professional Products Management Page
 * Books & eBooks eCommerce Platform
 *
 * Features:
 * - Advanced filtering and search
 * - Bulk actions
 * - Quick edit modal
 * - Professional table display
 * - Book-specific columns
 */

$page_title = 'Products';
require_once 'includes/admin-header.php';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $token = $_GET['token'] ?? '';
    $session_token = $_SESSION['delete_token'] ?? '';

    if ($token === $session_token && $token !== '') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product deleted successfully";
        } else {
            $_SESSION['error_message'] = "Failed to delete product";
        }
        $stmt->close();

        unset($_SESSION['delete_token']);
        header("Location: products-professional.php");
        exit();
    }
}

// Set delete token
$_SESSION['delete_token'] = md5(uniqid());

// Pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Build WHERE clause
$where = [];
$bind_types = '';
$bind_values = [];

if (!empty($search)) {
    $where[] = "(title LIKE ? OR author LIKE ? OR isbn_10 LIKE ? OR isbn_13 LIKE ?)";
    $search_param = "%$search%";
    $bind_types .= 'ssss';
    $bind_values = array_merge($bind_values, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $bind_types .= 's';
    $bind_values[] = $status_filter;
}

if (!empty($category_filter)) {
    $where[] = "category_id = ?";
    $bind_types .= 'i';
    $bind_values[] = (int)$category_filter;
}

if (!empty($type_filter)) {
    $where[] = "product_type = ?";
    $bind_types .= 's';
    $bind_values[] = $type_filter;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM products $where_clause";
$count_stmt = $conn->prepare($count_query);
if (!empty($bind_values)) {
    $count_stmt->bind_param($bind_types, ...$bind_values);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total = $count_row['total'] ?? 0;
$total_pages = ceil($total / $per_page);

// Get products
$query = "
    SELECT id, title, author, isbn_10, isbn_13, price, stock_quantity,
           status, featured, product_type, created_at, category_id, sku
    FROM products
    $where_clause
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$bind_types .= 'ii';
$bind_values[] = $per_page;
$bind_values[] = $offset;

$stmt = $conn->prepare($query);
if (!empty($bind_values)) {
    $stmt->bind_param($bind_types, ...$bind_values);
}
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// Get categories for filter
$categories = [];
$cat_query = "SELECT id, name FROM categories WHERE active = 1 ORDER BY name";
$cat_result = $conn->query($cat_query);
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>


<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success mb-3">
        <i class="bi bi-check-circle"></i>
        <div>
            <strong>Success!</strong><br>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger mb-3">
        <i class="bi bi-exclamation-circle"></i>
        <div>
            <strong>Error!</strong><br>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Page Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Products</h1>
        <p style="color: #7f8c8d; margin: 0;">Manage your books and eBooks catalog</p>
    </div>
    <a href="product-add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i>
        Add New Product
    </a>
</div>

<!-- Stats Overview -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="card" style="padding: 1rem; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; color: #1a3a52;"><?php echo $total; ?></div>
        <div style="font-size: 0.9rem; color: #7f8c8d;">Total Products</div>
    </div>
    <div class="card" style="padding: 1rem; text-align: center;">
        <?php
        $featured_count = 0;
        foreach ($products as $p) {
            if ($p['featured']) $featured_count++;
        }
        ?>
        <div style="font-size: 1.5rem; font-weight: bold; color: #f39c12;"><?php echo $featured_count; ?></div>
        <div style="font-size: 0.9rem; color: #7f8c8d;">Featured</div>
    </div>
    <div class="card" style="padding: 1rem; text-align: center;">
        <?php
        $low_stock = 0;
        foreach ($products as $p) {
            if ($p['stock_quantity'] <= 5) $low_stock++;
        }
        ?>
        <div style="font-size: 1.5rem; font-weight: bold; color: #e74c3c;"><?php echo $low_stock; ?></div>
        <div style="font-size: 0.9rem; color: #7f8c8d;">Low Stock</div>
    </div>
</div>

<!-- Filters Section -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3>
            <i class="bi bi-funnel"></i>
            Filters & Search
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <!-- Search -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Search by Title, Author, or ISBN</label>
                    <input
                        type="text"
                        name="search"
                        placeholder="e.g., Harry Potter, J.K. Rowling"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <!-- Status Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="discontinued" <?php echo $status_filter === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Product Type Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Product Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="Physical Book" <?php echo $type_filter === 'Physical Book' ? 'selected' : ''; ?>>Physical Book</option>
                        <option value="Digital Book" <?php echo $type_filter === 'Digital Book' ? 'selected' : ''; ?>>Digital Book</option>
                        <option value="Affiliate Link" <?php echo $type_filter === 'Affiliate Link' ? 'selected' : ''; ?>>Affiliate Link</option>
                        <option value="Both Types" <?php echo $type_filter === 'Both Types' ? 'selected' : ''; ?>>Both Types</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                    Apply Filters
                </button>
                <a href="products-professional.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="table-wrapper">
        <?php if (!empty($products)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title & Author</th>
                        <th>ISBN / SKU</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars(substr($product['title'], 0, 40)); ?></strong>
                                    <br>
                                    <small style="color: #7f8c8d;">by <?php echo htmlspecialchars($product['author']); ?></small>
                                </div>
                            </td>
                            <td style="font-size: 0.85rem; color: #7f8c8d;">
                                ISBN: <?php echo htmlspecialchars($product['isbn_10'] ?: 'N/A'); ?><br>
                                SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($product['product_type'] ?: 'Physical'); ?></small>
                            </td>
                            <td>
                                <strong>₹<?php echo number_format($product['price'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'primary'; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['featured']): ?>
                                    <i class="bi bi-star-fill" style="color: #f39c12; font-size: 1.1rem;"></i>
                                <?php else: ?>
                                    <i class="bi bi-star" style="color: #ccc;"></i>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.85rem; color: #7f8c8d;">
                                <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="product-add.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="products-professional.php?delete=1&id=<?php echo $product['id']; ?>&token=<?php echo $_SESSION['delete_token']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this product?');" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 3rem; text-align: center; color: #7f8c8d;">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                <p style="font-size: 1.1rem; margin-top: 1rem;">No products found</p>
                <a href="product-add.php" class="btn btn-primary" style="display: inline-block; margin-top: 1rem;">
                    <i class="bi bi-plus-circle"></i>
                    Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap;">
        <?php if ($page > 1): ?>
            <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">First</a>
            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">Previous</a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);

        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">Next</a>
            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">Last</a>
        <?php endif; ?>
    </div>
    <div style="text-align: center; margin-top: 1rem; color: #7f8c8d; font-size: 0.9rem;">
        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total; ?> total products)
    </div>
<?php endif; ?>

<?php require_once 'includes/admin-footer.php'; ?>