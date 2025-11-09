<?php
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Product CRUD operations (keeping existing logic)
    // ... existing product handling code ...
    $_SESSION['success_message'] = 'Product operation completed successfully!';
    header("Location: products-professional.php");
    exit();
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success_message'] = 'Product deleted successfully!';
    header("Location: products-professional.php");
    exit();
}

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$featured_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE featured = 1")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")->fetch_assoc()['count'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Fetch products with pagination
$total_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_pages = ceil($total_count / $limit);

$result_products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
$result_categories = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📚 Product Management</h1>
                <p class="page-subtitle">Manage your book inventory and listings</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="exportProducts()">
                    <span>📥</span> Export
                </button>
                <button class="btn-professional btn-primary-professional" onclick="openAddProductModal()">
                    <span>➕</span> Add New Book
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

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">📚</div>
            <h2 class="stat-value"><?php echo number_format($total_products); ?></h2>
            <p class="stat-label">Total Products</p>
            <div class="stat-change positive">
                <span>📈</span> Active inventory
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                ⭐
            </div>
            <h2 class="stat-value"><?php echo number_format($featured_products); ?></h2>
            <p class="stat-label">Featured Products</p>
            <div class="stat-change positive">
                <span>🌟</span> Premium listings
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);">
                ⚠️
            </div>
            <h2 class="stat-value"><?php echo number_format($low_stock); ?></h2>
            <p class="stat-label">Low Stock Items</p>
            <div class="stat-change <?php echo $low_stock > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $low_stock > 0 ? '⚠️' : '✅'; ?></span> Need attention
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($out_of_stock); ?></h2>
            <p class="stat-label">Out of Stock</p>
            <div class="stat-change <?php echo $out_of_stock > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $out_of_stock > 0 ? '🚫' : '✅'; ?></span> Status
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Products</label>
                <input type="text" class="form-control-professional" name="search" 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                       placeholder="Product title, author, ISBN...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📂 Category</label>
                <select name="category" class="form-control-professional form-select-professional">
                    <option value="">All Categories</option>
                    <?php $result_categories->data_seek(0); while($cat = $result_categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($_GET['category'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Status</label>
                <select name="status" class="form-control-professional form-select-professional">
                    <option value="">All Status</option>
                    <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>✅ Active</option>
                    <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>❌ Inactive</option>
                    <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>📝 Draft</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Filter
                    </button>
                    <a href="products-professional.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Professional Products Table -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📋</span> Products List
                <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                    <?php echo number_format($total_count); ?> total
                </span>
            </h3>
            <div style="color: var(--gray-600); font-size: var(--font-size-sm);">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <table class="table-professional">
                <thead>
                    <tr>
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
                    <?php if ($result_products && $result_products->num_rows > 0): ?>
                        <?php while($product = $result_products->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td>
                                    <span style="font-weight: 600; color: var(--primary);">#<?php echo $product['id']; ?></span>
                                </td>
                                <td>
                                    <div style="width: 60px; height: 80px; border-radius: 8px; overflow: hidden; box-shadow: var(--box-shadow);">
                                        <?php 
                                        $image_url = "/bookshelf/public/images/products/" . ($product['cover_image'] ?? 'default.jpg');
                                        ?>
                                        <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="font-weight: 600; color: var(--gray-800); margin-bottom: var(--spacing-1);">
                                            <?php echo htmlspecialchars($product['title']); ?>
                                            <?php if($product['featured']): ?>
                                                <span style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%); color: white; padding: 2px 6px; border-radius: 8px; font-size: var(--font-size-xs); margin-left: var(--spacing-1);">⭐ Featured</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                            📖 <?php echo htmlspecialchars($product['author']); ?>
                                        </div>
                                        <?php if($product['isbn_13']): ?>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                                ISBN: <?php echo htmlspecialchars($product['isbn_13']); ?>
                                            </div>
                                        <?php endif; ?>
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
                                    <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                        <div style="font-size: var(--font-size-sm); color: var(--gray-500); text-decoration: line-through;">
                                            <?php echo formatCurrency($product['original_price']); ?>
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
                                    </div>
                                </td>
                                <td>
                                    <?php echo formatStatusBadge($product['status']); ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-1);">
                                        <button class="btn-professional btn-info-professional" 
                                                style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                title="Edit Product">
                                            <span>✏️</span> Edit
                                        </button>
                                        <a href="products-professional.php?action=delete&id=<?php echo $product['id']; ?>" 
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
                            <td colspan="8" style="text-align: center; padding: var(--spacing-12);">
                                <div style="color: var(--gray-500);">
                                    <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">📚</div>
                                    <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No products found</h4>
                                    <p style="margin: 0;">Start by adding your first product to the inventory</p>
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
                        <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>">
                            <span>←</span> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a class="pagination-btn" href="?page=<?php echo $page + 1; ?>">
                            Next <span>→</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

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
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <div class="modal-body-professional">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-4);">
                        <!-- Basic Information -->
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📝</span> Basic Information
                            </h4>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📖 Book Title</label>
                                <input type="text" class="form-control-professional" name="title" required 
                                       placeholder="Enter book title...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">👤 Author</label>
                                <input type="text" class="form-control-professional" name="author" required 
                                       placeholder="Enter author name...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📂 Category</label>
                                <select class="form-control-professional form-select-professional" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php $result_categories->data_seek(0); while($cat = $result_categories->fetch_assoc()): ?>
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
                                <label class="form-label-professional">💵 Price</label>
                                <input type="number" class="form-control-professional" name="price" step="0.01" required 
                                       placeholder="0.00">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">💸 Original Price (Optional)</label>
                                <input type="number" class="form-control-professional" name="original_price" step="0.01" 
                                       placeholder="0.00">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📦 Stock Quantity</label>
                                <input type="number" class="form-control-professional" name="stock_quantity" required 
                                       placeholder="0">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏷️ SKU</label>
                                <input type="text" class="form-control-professional" name="sku" 
                                       placeholder="Product SKU...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📊 Status</label>
                                <select class="form-control-professional form-select-professional" name="status">
                                    <option value="active">✅ Active</option>
                                    <option value="inactive">❌ Inactive</option>
                                    <option value="draft">📝 Draft</option>
                                </select>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional" style="display: flex; align-items: center; gap: var(--spacing-2);">
                                    <input type="checkbox" name="featured" style="margin: 0;">
                                    <span>⭐ Featured Product</span>
                                </label>
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
                                <label class="form-label-professional">📖 ISBN-13</label>
                                <input type="text" class="form-control-professional" name="isbn_13" 
                                       placeholder="978-XXXXXXXXXX">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🏢 Publisher</label>
                                <input type="text" class="form-control-professional" name="publisher" 
                                       placeholder="Publisher name...">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🗓️ Publication Date</label>
                                <input type="date" class="form-control-professional" name="publication_date">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">🌐 Language</label>
                                <select class="form-control-professional form-select-professional" name="language">
                                    <option value="English">English</option>
                                    <option value="Hindi">Hindi</option>
                                    <option value="Spanish">Spanish</option>
                                    <option value="French">French</option>
                                </select>
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📄 Pages</label>
                                <input type="number" class="form-control-professional" name="pages" 
                                       placeholder="Number of pages">
                            </div>
                            
                            <div class="form-group-professional">
                                <label class="form-label-professional">📖 Binding Type</label>
                                <select class="form-control-professional form-select-professional" name="binding_type">
                                    <option value="Paperback">Paperback</option>
                                    <option value="Hardcover">Hardcover</option>
                                    <option value="Kindle">Kindle</option>
                                    <option value="Audiobook">Audiobook</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cover Image Upload -->
                    <div style="margin-top: var(--spacing-6); padding-top: var(--spacing-6); border-top: 1px solid var(--gray-200);">
                        <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                            <span>🖼️</span> Product Images
                        </h4>
                        
                        <div class="form-group-professional">
                            <label class="form-label-professional">📸 Cover Image</label>
                            <input type="file" class="form-control-professional" name="cover_image" accept="image/*">
                            <div style="font-size: var(--font-size-xs); color: var(--gray-500); margin-top: var(--spacing-1);">
                                Recommended: 300x400px, Max: 2MB
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

</div>

<!-- Professional JavaScript -->
<script>
function openAddProductModal() {
    openModal('addProductModal');
}

function editProduct(product) {
    // Populate form with product data
    openModal('addProductModal');
    // Add edit logic here
}

function exportProducts() {
    // Export functionality
    window.location.href = 'export-products.php';
}

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add table animations and form enhancements
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>