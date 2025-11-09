<?php
// admin/amazon-inventory-manager.php - Amazon-like inventory management
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                // Handle product creation
                $name = $_POST['name'] ?? '';
                $author = $_POST['author'] ?? '';
                $description = $_POST['description'] ?? '';
                $price = floatval($_POST['price'] ?? 0);
                $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
                $category_id = intval($_POST['category_id'] ?? 0);
                $isbn = $_POST['isbn'] ?? '';
                $publisher = $_POST['publisher'] ?? '';
                $sku = $_POST['sku'] ?? '';
                $status = $_POST['status'] ?? 'published';
                
                // Generate SKU if not provided
                if (empty($sku)) {
                    $sku = 'BOOK-' . strtoupper(substr(md5(time() . $name), 0, 8));
                }
                
                $stmt = $conn->prepare("INSERT INTO products (name, author, description, price, stock_quantity, category_id, isbn, publisher, sku, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $name, SQLITE3_TEXT);
                $stmt->bindValue(2, $author, SQLITE3_TEXT);
                $stmt->bindValue(3, $description, SQLITE3_TEXT);
                $stmt->bindValue(4, $price, SQLITE3_FLOAT);
                $stmt->bindValue(5, $stock_quantity, SQLITE3_INTEGER);
                $stmt->bindValue(6, $category_id, SQLITE3_INTEGER);
                $stmt->bindValue(7, $isbn, SQLITE3_TEXT);
                $stmt->bindValue(8, $publisher, SQLITE3_TEXT);
                $stmt->bindValue(9, $sku, SQLITE3_TEXT);
                $stmt->bindValue(10, $status, SQLITE3_TEXT);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Product added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add product.";
                }
                header("Location: amazon-inventory-manager.php");
                exit();
                
            case 'update_stock':
                // Handle stock update
                $product_id = intval($_POST['product_id']);
                $new_stock = intval($_POST['stock_quantity']);
                
                $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $stmt->bindValue(1, $new_stock, SQLITE3_INTEGER);
                $stmt->bindValue(2, $product_id, SQLITE3_INTEGER);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Stock updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update stock.";
                }
                header("Location: amazon-inventory-manager.php");
                exit();
                
            case 'bulk_update':
                // Handle bulk actions
                if (isset($_POST['selected_products']) && isset($_POST['bulk_action'])) {
                    $selected_products = $_POST['selected_products'];
                    $bulk_action = $_POST['bulk_action'];
                    
                    switch ($bulk_action) {
                        case 'delete':
                            $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                            $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                            foreach ($selected_products as $index => $id) {
                                $stmt->bindValue($index + 1, $id, SQLITE3_INTEGER);
                            }
                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = count($selected_products) . " products deleted successfully!";
                            } else {
                                $_SESSION['error_message'] = "Failed to delete products.";
                            }
                            break;
                            
                        case 'publish':
                            $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                            $stmt = $conn->prepare("UPDATE products SET status = 'published' WHERE id IN ($placeholders)");
                            foreach ($selected_products as $index => $id) {
                                $stmt->bindValue($index + 1, $id, SQLITE3_INTEGER);
                            }
                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = count($selected_products) . " products published!";
                            } else {
                                $_SESSION['error_message'] = "Failed to publish products.";
                            }
                            break;
                            
                        case 'unpublish':
                            $placeholders = str_repeat('?,', count($selected_products) - 1) . '?';
                            $stmt = $conn->prepare("UPDATE products SET status = 'draft' WHERE id IN ($placeholders)");
                            foreach ($selected_products as $index => $id) {
                                $stmt->bindValue($index + 1, $id, SQLITE3_INTEGER);
                            }
                            if ($stmt->execute()) {
                                $_SESSION['success_message'] = count($selected_products) . " products unpublished!";
                            } else {
                                $_SESSION['error_message'] = "Failed to unpublish products.";
                            }
                            break;
                    }
                }
                header("Location: amazon-inventory-manager.php");
                exit();
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bindValue(1, $id_to_delete, SQLITE3_INTEGER);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Product deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete product.';
    }
    header("Location: amazon-inventory-manager.php");
    exit();
}

// Filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$stock_status_filter = $_GET['stock_status'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR author LIKE ? OR isbn LIKE ? OR sku LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($category_filter)) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_filter;
}

if (!empty($stock_status_filter)) {
    switch ($stock_status_filter) {
        case 'in_stock':
            $where_conditions[] = "stock_quantity > 10";
            break;
        case 'low_stock':
            $where_conditions[] = "stock_quantity <= 10 AND stock_quantity > 0";
            break;
        case 'out_of_stock':
            $where_conditions[] = "stock_quantity = 0";
            break;
    }
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM products $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    foreach ($params as $index => $param) {
        $count_stmt->bindValue($index + 1, $param, SQLITE3_TEXT);
    }
    $count_result = $count_stmt->execute();
} else {
    $count_result = $conn->query($count_sql);
}
$total_products = $count_result->fetchArray(SQLITE3_ASSOC)['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where_clause ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
if (!empty($params)) {
    $products_stmt = $conn->prepare($products_sql);
    foreach ($params as $index => $param) {
        $products_stmt->bindValue($index + 1, $param, SQLITE3_TEXT);
    }
    $products_result = $products_stmt->execute();
} else {
    $products_result = $conn->query($products_sql);
}

// Get categories for filter
$categories_result = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

// Get statistics
$total_products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetchArray(SQLITE3_ASSOC)['count'];
$published_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'published'")->fetchArray(SQLITE3_ASSOC)['count'];
$draft_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'draft'")->fetchArray(SQLITE3_ASSOC)['count'];
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10 AND stock_quantity > 0")->fetchArray(SQLITE3_ASSOC)['count'];
$out_of_stock_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")->fetchArray(SQLITE3_ASSOC)['count'];

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Amazon-like Header -->
    <div class="page-header" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📚 Inventory Manager</h1>
                <p class="page-subtitle">Manage your book inventory like a professional seller</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="exportInventory()">
                    <span>📥</span> Export
                </button>
                <button class="btn-professional btn-primary-professional" onclick="openAddProductModal()">
                    <span>➕</span> Add New Product
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php renderProfessionalAlert('success', $_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <?php renderProfessionalAlert('error', $_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Inventory Statistics -->
    <div class="stats-grid">
        <div class="stat-card-professional" style="border-left-color: #146eb4;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #146eb4 0%, #232f3e 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($total_products_count); ?></h2>
            <p class="stat-label">Total Products</p>
            <div class="stat-change positive">
                <span>📈</span> In catalog
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #232f3e;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
                ✅
            </div>
            <h2 class="stat-value"><?php echo number_format($published_products); ?></h2>
            <p class="stat-label">Published Items</p>
            <div class="stat-change positive">
                <span>🛒</span> Live on store
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #ff9900;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9900 0%, #ffd700 100%);">
                ⚠️
            </div>
            <h2 class="stat-value"><?php echo number_format($low_stock_count); ?></h2>
            <p class="stat-label">Low Stock Items</p>
            <div class="stat-change <?php echo $low_stock_count > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $low_stock_count > 0 ? '⚠️' : '✅'; ?></span> Need attention
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #b12704;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #b12704 0%, #ff6347 100%);">
                🚫
            </div>
            <h2 class="stat-value"><?php echo number_format($out_of_stock_count); ?></h2>
            <p class="stat-label">Out of Stock</p>
            <div class="stat-change <?php echo $out_of_stock_count > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $out_of_stock_count > 0 ? '🚫' : '✅'; ?></span> Status
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Products</label>
                <input type="text" class="form-control-professional" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Product title, author, ISBN, SKU...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📂 Category</label>
                <select name="category" class="form-control-professional form-select-professional">
                    <option value="">All Categories</option>
                    <?php while($cat = $categories_result->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Stock Status</label>
                <select name="stock_status" class="form-control-professional form-select-professional">
                    <option value="">All Stock Status</option>
                    <option value="in_stock" <?php echo ($stock_status_filter == 'in_stock') ? 'selected' : ''; ?>>✅ In Stock</option>
                    <option value="low_stock" <?php echo ($stock_status_filter == 'low_stock') ? 'selected' : ''; ?>>⚠️ Low Stock</option>
                    <option value="out_of_stock" <?php echo ($stock_status_filter == 'out_of_stock') ? 'selected' : ''; ?>>🚫 Out of Stock</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">🏷️ Publication Status</label>
                <select name="status" class="form-control-professional form-select-professional">
                    <option value="">All Status</option>
                    <option value="published" <?php echo ($status_filter == 'published') ? 'selected' : ''; ?>>✅ Published</option>
                    <option value="draft" <?php echo ($status_filter == 'draft') ? 'selected' : ''; ?>>📝 Draft</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Filter
                    </button>
                    <a href="amazon-inventory-manager.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Professional Products Table with Bulk Actions -->
    <form method="POST" id="bulkActionForm">
        <input type="hidden" name="action" value="bulk_update">
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>📋</span> Inventory List
                    <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                        <?php echo number_format($total_products); ?> items
                    </span>
                </h3>
                <div style="display: flex; gap: var(--spacing-2); align-items: center;">
                    <select name="bulk_action" class="form-control-professional form-select-professional" style="font-size: var(--font-size-sm); padding: var(--spacing-1) var(--spacing-2); min-width: 150px;">
                        <option value="">Bulk Actions</option>
                        <option value="publish">✅ Publish Selected</option>
                        <option value="unpublish">📝 Unpublish Selected</option>
                        <option value="delete">🗑️ Delete Selected</option>
                    </select>
                    <button type="submit" class="btn-professional btn-outline-professional" style="padding: var(--spacing-1) var(--spacing-3); font-size: var(--font-size-sm);" onclick="return confirmBulkAction()">
                        Apply
                    </button>
                    <div style="color: var(--gray-600); font-size: var(--font-size-sm);">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </div>
                </div>
            </div>
            <div class="card-body-professional" style="padding: 0;">
                <table class="table-professional">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" style="transform: scale(1.3);">
                            </th>
                            <th>ID</th>
                            <th>Cover</th>
                            <th>Product Details</th>
                            <th>Category</th>
                            <th>Pricing</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products_result && $products_result->numRows() > 0): ?>
                            <?php while($product = $products_result->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr class="fade-in">
                                    <td>
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox">
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: var(--primary);">#<?php echo $product['id']; ?></span>
                                    </td>
                                    <td>
                                        <div style="width: 60px; height: 80px; border-radius: 8px; overflow: hidden; box-shadow: var(--box-shadow);">
                                            <?php 
                                            $image_url = "/bookshelf/public/images/" . ($product['image'] ?? 'default.jpg');
                                            ?>
                                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                 onerror="this.src='/bookshelf/public/images/default.jpg'">
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-800); margin-bottom: var(--spacing-1);">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </div>
                                            <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                                📖 <?php echo htmlspecialchars($product['author']); ?>
                                            </div>
                                            <?php if($product['isbn']): ?>
                                                <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                                    ISBN: <?php echo htmlspecialchars($product['isbn']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-top: var(--spacing-1);">
                                                SKU: <?php echo htmlspecialchars($product['sku']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="background: var(--gray-200); color: var(--gray-700); padding: 4px 8px; border-radius: 12px; font-size: var(--font-size-xs);">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: var(--font-size-lg); color: var(--success);">
                                            <?php echo formatCurrency($product['price']); ?>
                                        </div>
                                        <?php if($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                            <div style="font-size: var(--font-size-sm); color: var(--gray-500); text-decoration: line-through;">
                                                <?php echo formatCurrency($product['sale_price']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $stock = $product['stock_quantity'];
                                        $stockClass = $stock == 0 ? 'danger' : ($stock <= 10 ? 'warning' : 'success');
                                        $stockIcon = $stock == 0 ? '🚫' : ($stock <= 10 ? '⚠️' : '✅');
                                        ?>
                                        <div style="display: flex; align-items: center; gap: var(--spacing-2);">
                                            <span><?php echo $stockIcon; ?></span>
                                            <span style="font-weight: 600;"><?php echo $stock; ?></span>
                                            <button type="button" class="btn-professional btn-outline-professional" style="padding: var(--spacing-1); font-size: var(--font-size-xs);" onclick="openStockModal(<?php echo $product['id']; ?>, <?php echo $stock; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo formatStatusBadge($product['status']); ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: var(--spacing-1);">
                                            <button type="button" class="btn-professional btn-info-professional" 
                                                    style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                    onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                    title="Edit Product">
                                                <span>✏️</span> Edit
                                            </button>
                                            <a href="amazon-inventory-manager.php?action=delete&id=<?php echo $product['id']; ?>" 
                                               class="btn-professional btn-danger-professional" 
                                               style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                               onclick="return confirm('Are you sure you want to delete this product?')" 
                                               title="Delete Product">
                                                <span>🗑️</span> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: var(--spacing-12);">
                                    <div style="color: var(--gray-500);">
                                        <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">📚</div>
                                        <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No products found</h4>
                                        <p style="margin: 0;">Try adjusting your filters or add your first product to the inventory</p>
                                        <button class="btn-professional btn-primary-professional" style="margin-top: var(--spacing-4);" onclick="openAddProductModal()">
                                            <span>➕</span> Add First Product
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Professional Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="padding: var(--spacing-4); border-top: 1px solid var(--gray-200); background: var(--gray-100);">
                    <div class="pagination-professional">
                        <?php if ($page > 1): ?>
                            <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock_status=<?php echo $stock_status_filter; ?>&status=<?php echo $status_filter; ?>">
                                <span>←</span> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock_status=<?php echo $stock_status_filter; ?>&status=<?php echo $status_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a class="pagination-btn" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&stock_status=<?php echo $stock_status_filter; ?>&status=<?php echo $status_filter; ?>">
                                Next <span>→</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Professional Add Product Modal -->
    <div class="modal-professional" id="addProductModal">
        <div class="modal-content-professional" style="max-width: 800px; width: 90%;">
            <div class="modal-header-professional">
                <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-2);">
                    <span>➕</span> Add New Product
                </h3>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('addProductModal')">
                    <span>❌</span>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <div class="modal-body-professional">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-4);">
                        <!-- Basic Information -->
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📝</span> Basic Information
                            </h4>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📖 Book Title *</label>
                                <input type="text" class="form-control-professional" name="name" required 
                                       placeholder="Enter book title...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">👤 Author *</label>
                                <input type="text" class="form-control-professional" name="author" required 
                                       placeholder="Enter author name...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📂 Category</label>
                                <select class="form-control-professional form-select-professional" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php 
                                    // Reset categories result for reuse
                                    $categories_result = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
                                    while($cat = $categories_result->fetchArray(SQLITE3_ASSOC)): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📄 Description</label>
                                <textarea class="form-control-professional" name="description" rows="4" 
                                          placeholder="Enter product description..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Pricing & Inventory -->
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>💰</span> Pricing & Inventory
                            </h4>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">💵 Price *</label>
                                <input type="number" class="form-control-professional" name="price" step="0.01" required 
                                       placeholder="0.00">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📦 Stock Quantity *</label>
                                <input type="number" class="form-control-professional" name="stock_quantity" required 
                                       placeholder="0" value="0">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏷️ SKU (Optional)</label>
                                <input type="text" class="form-control-professional" name="sku" 
                                       placeholder="Product SKU...">
                                <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-top: var(--spacing-1);">
                                    Leave blank to auto-generate
                                </div>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📊 Status</label>
                                <select class="form-control-professional form-select-professional" name="status">
                                    <option value="published">✅ Published</option>
                                    <option value="draft" selected>📝 Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Fields -->
                    <div style="margin-top: var(--spacing-6); padding-top: var(--spacing-6); border-top: 1px solid var(--gray-200);">
                        <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                            <span>📚</span> Additional Details
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-4);">
                            <div class="form-group-professional">
                                <label class="form-label-professional">📖 ISBN</label>
                                <input type="text" class="form-control-professional" name="isbn" 
                                       placeholder="978-XXXXXXXXXX">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏢 Publisher</label>
                                <input type="text" class="form-control-professional" name="publisher" 
                                       placeholder="Publisher name...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer-professional">
                    <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('addProductModal')">
                        <span>❌</span> Cancel
                    </button>
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>💾</span> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Professional Stock Update Modal -->
    <div class="modal-professional" id="stockModal">
        <div class="modal-content-professional" style="max-width: 500px; width: 90%;">
            <div class="modal-header-professional">
                <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-2);">
                    <span>📦</span> Update Stock
                </h3>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('stockModal')">
                    <span>❌</span>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="product_id" id="stockProductId">
                <div class="modal-body-professional">
                    <div style="text-align: center; margin-bottom: var(--spacing-6);">
                        <h4 id="stockProductName" style="margin: 0 0 var(--spacing-2) 0; color: var(--gray-800);"></h4>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                            Current Stock: <span id="currentStock" style="font-weight: 700;"></span>
                        </div>
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📦 New Stock Quantity</label>
                        <input type="number" class="form-control-professional" name="stock_quantity" id="newStockQuantity" required 
                               placeholder="Enter new stock quantity..." min="0">
                    </div>
                </div>
                <div class="modal-footer-professional">
                    <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('stockModal')">
                        <span>❌</span> Cancel
                    </button>
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>💾</span> Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Professional JavaScript -->
<script>
function openAddProductModal() {
    openModal('addProductModal');
}

function openStockModal(productId, currentStock, productName) {
    document.getElementById('stockProductId').value = productId;
    document.getElementById('currentStock').textContent = currentStock;
    document.getElementById('stockProductName').textContent = productName;
    document.getElementById('newStockQuantity').value = currentStock;
    openModal('stockModal');
}

function editProduct(product) {
    // For now, redirect to the existing product edit page
    window.location.href = 'products-professional.php';
}

function exportInventory() {
    // Export functionality
    alert('Export functionality would be implemented here');
}

function confirmBulkAction() {
    const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
    const bulkAction = document.querySelector('[name="bulk_action"]').value;
    
    if (selectedProducts.length === 0) {
        alert('Please select at least one product');
        return false;
    }
    
    if (!bulkAction) {
        alert('Please select a bulk action');
        return false;
    }
    
    let message = `Are you sure you want to ${bulkAction} ${selectedProducts.length} product(s)?`;
    if (bulkAction === 'delete') {
        message = `⚠️ WARNING: This will permanently delete ${selectedProducts.length} product(s). This action cannot be undone.\n\n${message}`;
    }
    
    return confirm(message);
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add table animations and form enhancements
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
    
    // Form enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.style.opacity = '0.7';
                submitBtn.style.pointerEvents = 'none';
                submitBtn.innerHTML = '<span>🔄</span> Processing...';
            }
        });
    });
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>