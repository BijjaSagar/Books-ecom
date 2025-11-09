<?php
include '../includes/admin_header.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'add_category':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name)) {
                    $_SESSION['error_message'] = "Category name is required.";
                } else {
                    // Check if category already exists
                    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                    $check_stmt->bind_param("s", $name);
                    $check_stmt->execute();
                    
                    if ($check_stmt->get_result()->num_rows > 0) {
                        $_SESSION['error_message'] = "Category already exists.";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO categories (name, description, status, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->bind_param("sss", $name, $description, $status);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Category added successfully.";
                        } else {
                            $_SESSION['error_message'] = "Failed to add category.";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
                break;
                
            case 'edit_category':
                $category_id = intval($_POST['category_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $status = $_POST['status'] ?? 'active';
                
                if (empty($name)) {
                    $_SESSION['error_message'] = "Category name is required.";
                } else {
                    // Check if another category with same name exists
                    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                    $check_stmt->bind_param("si", $name, $category_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->get_result()->num_rows > 0) {
                        $_SESSION['error_message'] = "Another category with this name already exists.";
                    } else {
                        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $name, $description, $status, $category_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Category updated successfully.";
                        } else {
                            $_SESSION['error_message'] = "Failed to update category.";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
                break;
                
            case 'delete_category':
                $category_id = intval($_POST['category_id']);
                
                // Check if category has products
                $product_check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $product_check->bind_param("i", $category_id);
                $product_check->execute();
                $product_count = $product_check->get_result()->fetch_assoc()['count'];
                
                if ($product_count > 0) {
                    $_SESSION['error_message'] = "Cannot delete category with {$product_count} products. Move products first.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->bind_param("i", $category_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Category deleted successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete category.";
                    }
                    $stmt->close();
                }
                $product_check->close();
                break;
        }
    }
    header("Location: categories.php");
    exit();
}

// Pagination and search
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM categories $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_categories = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_categories = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_categories / $limit);

// Get categories with product count
$categories_sql = "SELECT c.*, COUNT(p.id) as product_count 
                   FROM categories c 
                   LEFT JOIN products p ON c.id = p.category_id 
                   $where_clause 
                   GROUP BY c.id 
                   ORDER BY c.created_at DESC 
                   LIMIT ? OFFSET ?";

$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";

$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param($final_types, ...$final_params);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();
?>

<!-- Professional Admin Styles -->
<link rel="stylesheet" href="assets/admin-professional.css">

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">🏷️ Category Management</h1>
                <p class="page-subtitle">Organize your products into categories</p>
            </div>
            <button class="btn-professional btn-primary-professional" onclick="openAddCategoryModal()">
                <span>➕</span> Add Category
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert-professional alert-success-professional fade-in">
            <span>✅</span>
            <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert-professional alert-danger-professional fade-in">
            <span>⚠️</span>
            <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">📊</div>
            <h2 class="stat-value"><?php echo number_format($total_categories); ?></h2>
            <p class="stat-label">Total Categories</p>
            <div class="stat-change positive">
                <span>📈</span> Active management
            </div>
        </div>
        
        <?php
        // Get additional stats
        $active_categories = $conn->query("SELECT COUNT(*) as count FROM categories WHERE status = 'active'")->fetch_assoc()['count'];
        $categories_with_products = $conn->query("SELECT COUNT(DISTINCT category_id) as count FROM products WHERE category_id IS NOT NULL")->fetch_assoc()['count'];
        ?>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                ✅
            </div>
            <h2 class="stat-value"><?php echo number_format($active_categories); ?></h2>
            <p class="stat-label">Active Categories</p>
            <div class="stat-change positive">
                <span>📊</span> <?php echo number_format(($active_categories / max($total_categories, 1)) * 100, 1); ?>% active
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($categories_with_products); ?></h2>
            <p class="stat-label">Categories with Products</p>
            <div class="stat-change positive">
                <span>🛍️</span> In use
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                📈
            </div>
            <h2 class="stat-value"><?php echo $total_pages; ?></h2>
            <p class="stat-label">Total Pages</p>
            <div class="stat-change">
                <span>📋</span> Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Categories</label>
                <input type="text" class="form-control-professional" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Category name or description...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Status Filter</label>
                <select name="status" class="form-control-professional form-select-professional">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Filter
                    </button>
                    <a href="categories.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
    <!-- Professional Categories Table -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📋</span> Categories List
                <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                    <?php echo number_format($total_categories); ?> total
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
                        <th>Category</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categories_result && $categories_result->num_rows > 0): ?>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td>
                                    <span style="font-weight: 600; color: var(--primary);">#<?php echo $category['id']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-3);">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                            🏷️
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($category['name']); ?></div>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500);">Category ID: <?php echo $category['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: var(--gray-600); font-size: var(--font-size-sm);">
                                        <?php 
                                        $desc = $category['description'] ?? 'No description';
                                        echo htmlspecialchars(strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-2);">
                                        <span style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%); color: white; padding: 4px 8px; border-radius: 12px; font-size: var(--font-size-xs); font-weight: 600;">
                                            <?php echo $category['product_count']; ?> products
                                        </span>
                                        <?php if ($category['product_count'] > 0): ?>
                                            <span>📦</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge-professional status-<?php echo $category['status']; ?>">
                                        <?php echo $category['status'] === 'active' ? '✅' : '❌'; ?>
                                        <?php echo ucfirst($category['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="color: var(--gray-600);">
                                        <div style="font-weight: 500;"><?php echo date('M j, Y', strtotime($category['created_at'])); ?></div>
                                        <div style="font-size: var(--font-size-xs);"><?php echo date('g:i A', strtotime($category['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-1);">
                                        <button class="btn-professional btn-info-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Edit Category">
                                            <span>✏️</span> Edit
                                        </button>
                                        <a href="products.php?category=<?php echo $category['id']; ?>" 
                                           class="btn-professional btn-success-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" title="View Products">
                                            <span>👁️</span> View
                                        </a>
                                        <?php if ($category['product_count'] == 0): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn-professional btn-danger-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                        onclick="return confirm('Are you sure you want to delete this category?')" title="Delete Category">
                                                    <span>🗑️</span> Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs); background: var(--gray-300); color: var(--gray-600); cursor: not-allowed;" 
                                                    disabled title="Cannot delete category with products">
                                                <span>🚫</span> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: var(--spacing-12);">
                                <div style="color: var(--gray-500);">
                                    <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">🏷️</div>
                                    <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No categories found</h4>
                                    <p style="margin: 0;">Start by creating your first category</p>
                                    <button class="btn-professional btn-primary-professional" style="margin-top: var(--spacing-4);" onclick="openAddCategoryModal()">
                                        <span>➕</span> Add First Category
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Professional Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-professional">
            <?php if ($page > 1): ?>
                <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                    <span>←</span> Previous
                </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a class="pagination-btn" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                    Next <span>→</span>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Professional Add Category Modal -->
    <div class="modal-professional" id="addCategoryModal">
        <div class="modal-content-professional">
            <div class="modal-header-professional">
                <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-2);">
                    <span>➕</span> Add New Category
                </h3>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('addCategoryModal')">
                    <span>❌</span>
                </button>
            </div>
            <form method="POST" class="category-form">
                <div class="modal-body-professional">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">🏷️ Category Name</label>
                        <input type="text" class="form-control-professional" name="name" required 
                               placeholder="Enter category name..." maxlength="100">
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📝 Description</label>
                        <textarea class="form-control-professional" name="description" rows="3" 
                                  placeholder="Optional description for this category..." maxlength="500"></textarea>
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📊 Status</label>
                        <select class="form-control-professional form-select-professional" name="status">
                            <option value="active">✅ Active</option>
                            <option value="inactive">❌ Inactive</option>
                        </select>
                    </div>
                    
                    <div class="alert-professional alert-info-professional">
                        <span>📊</span>
                        <span>Active categories will be visible to customers and available for product assignment.</span>
                    </div>
                </div>
                <div class="modal-footer-professional">
                    <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('addCategoryModal')">
                        <span>❌</span> Cancel
                    </button>
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>➕</span> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Professional Edit Category Modal -->
    <div class="modal-professional" id="editCategoryModal">
        <div class="modal-content-professional">
            <div class="modal-header-professional">
                <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-2);">
                    <span>✏️</span> Edit Category
                </h3>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('editCategoryModal')">
                    <span>❌</span>
                </button>
            </div>
            <form method="POST" class="category-form">
                <div class="modal-body-professional">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">🏷️ Category Name</label>
                        <input type="text" class="form-control-professional" name="name" id="edit_name" required 
                               placeholder="Enter category name..." maxlength="100">
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📝 Description</label>
                        <textarea class="form-control-professional" name="description" id="edit_description" rows="3" 
                                  placeholder="Optional description for this category..." maxlength="500"></textarea>
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📊 Status</label>
                        <select class="form-control-professional form-select-professional" name="status" id="edit_status">
                            <option value="active">✅ Active</option>
                            <option value="inactive">❌ Inactive</option>
                        </select>
                    </div>
                    
                    <div class="alert-professional alert-warning-professional">
                        <span>⚠️</span>
                        <span>Changes to this category will affect all associated products.</span>
                    </div>
                </div>
                <div class="modal-footer-professional">
                    <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('editCategoryModal')">
                        <span>❌</span> Cancel
                    </button>
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>💾</span> Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Professional JavaScript -->
<script>
// Professional Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        
        // Reset form if it's the add modal
        if (modalId === 'addCategoryModal') {
            modal.querySelector('form').reset();
        }
    }
}

function openAddCategoryModal() {
    openModal('addCategoryModal');
}

function editCategory(category) {
    // Populate edit form
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_status').value = category.status;
    
    // Open edit modal
    openModal('editCategoryModal');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-professional')) {
        const modalId = e.target.id;
        closeModal(modalId);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal-professional.show');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// Professional form enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    const forms = document.querySelectorAll('.category-form');
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
    
    // Animate table rows on load
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
    
    // Search input enhancements
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Add visual feedback for search
                this.style.borderColor = 'var(--primary)';
                setTimeout(() => {
                    this.style.borderColor = 'var(--gray-300)';
                }, 500);
            }, 300);
        });
    }
    
    // Professional tooltips for action buttons
    const actionButtons = document.querySelectorAll('[title]');
    actionButtons.forEach(btn => {
        btn.addEventListener('mouseenter', function(e) {
            // Simple tooltip implementation
            const tooltip = document.createElement('div');
            tooltip.className = 'professional-tooltip';
            tooltip.textContent = this.getAttribute('title');
            tooltip.style.cssText = `
                position: absolute;
                background: var(--gray-800);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 1000;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            setTimeout(() => tooltip.style.opacity = '1', 10);
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            }, { once: true });
        });
    });
});

// Professional confirmation dialogs
function confirmDelete(categoryName, productCount) {
    if (productCount > 0) {
        alert(`⚠️ Cannot delete "${categoryName}" - it contains ${productCount} products. Please move the products to another category first.`);
        return false;
    }
    return confirm(`🗑️ Are you sure you want to delete the category "${categoryName}"?\n\nThis action cannot be undone.`);
}

// Auto-save form drafts (localStorage)
function saveDraft(formData, formType) {
    localStorage.setItem(`category_${formType}_draft`, JSON.stringify(formData));
}

function loadDraft(formType) {
    const draft = localStorage.getItem(`category_${formType}_draft`);
    return draft ? JSON.parse(draft) : null;
}

function clearDraft(formType) {
    localStorage.removeItem(`category_${formType}_draft`);
}
</script>

<?php include '../includes/admin_footer.php'; ?>
    text-transform: uppercase;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 0.25rem;
}

.pagination .page-link {
    color: #007bff;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}
</style>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_status').value = category.status;
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>

<?php include '../includes/admin_footer.php'; ?>