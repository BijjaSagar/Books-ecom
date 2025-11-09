<?php
include '../includes/admin_header.php';

// Include the MenuManager class
require_once '../includes/MenuManager.php';

// Initialize MenuManager
$menuManager = new MenuManager($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create_menu':
                $name = trim($_POST['name']);
                $location = trim($_POST['location']);
                $description = trim($_POST['description']);
                
                if (!empty($name) && !empty($location)) {
                    $stmt = $conn->prepare("INSERT INTO navigation_menus (name, location, description, status) VALUES (?, ?, ?, 'active')");
                    $stmt->bind_param("sss", $name, $location, $description);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Menu created successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to create menu.";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = "Menu name and location are required.";
                }
                break;
                
            case 'add_menu_item':
                $menu_id = intval($_POST['menu_id']);
                $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                $title = trim($_POST['title']);
                $url = trim($_POST['url']);
                $icon_class = trim($_POST['icon_class']);
                $sort_order = intval($_POST['sort_order']);
                
                if (!empty($title)) {
                    $data = [
                        'parent_id' => $parent_id,
                        'title' => $title,
                        'url' => $url,
                        'icon_class' => $icon_class,
                        'css_class' => '',
                        'description' => '',
                        'sort_order' => $sort_order,
                        'status' => 'active',
                        'visibility' => 'public'
                    ];
                    
                    if ($menuManager->addMenuItem($menu_id, $data)) {
                        $_SESSION['success_message'] = "Menu item added successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to add menu item.";
                    }
                } else {
                    $_SESSION['error_message'] = "Menu item title is required.";
                }
                break;
                
            case 'delete_menu_item':
                $item_id = intval($_POST['item_id']);
                
                $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->bind_param("i", $item_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Menu item deleted successfully.";
                    $menuManager->clearCache();
                } else {
                    $_SESSION['error_message'] = "Failed to delete menu item.";
                }
                $stmt->close();
                break;
                
            case 'rebuild_categories':
                if ($menuManager->rebuildCategoryMenus()) {
                    $_SESSION['success_message'] = "Category menus rebuilt successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to rebuild category menus.";
                }
                break;
                
            case 'clear_cache':
                if ($menuManager->clearCache()) {
                    $_SESSION['success_message'] = "Menu cache cleared successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to clear menu cache.";
                }
                break;
        }
    }
    header("Location: menu_manager.php");
    exit();
}

// Get all menus
$menus_query = "SELECT * FROM navigation_menus ORDER BY location, name";
$menus_result = $conn->query($menus_query);

// Get selected menu
$selected_menu_id = $_GET['menu_id'] ?? null;
$selected_menu = null;
$menu_items = [];

if ($selected_menu_id) {
    $menu_stmt = $conn->prepare("SELECT * FROM navigation_menus WHERE id = ?");
    $menu_stmt->bind_param("i", $selected_menu_id);
    $menu_stmt->execute();
    $selected_menu = $menu_stmt->get_result()->fetch_assoc();
    
    if ($selected_menu) {
        $items_query = "SELECT mi.*, c.name as category_name 
                       FROM menu_items mi 
                       LEFT JOIN categories c ON mi.category_id = c.id 
                       WHERE mi.menu_id = ? 
                       ORDER BY mi.sort_order, mi.title";
        $items_stmt = $conn->prepare($items_query);
        $items_stmt->bind_param("i", $selected_menu_id);
        $items_stmt->execute();
        $menu_items = $items_stmt->get_result();
    }
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories WHERE status = 'active' ORDER BY name";
$categories_result = $conn->query($categories_query);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h1>Menu Manager</h1>
            <p class="text-muted mb-0">Manage your website navigation menus</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMenuModal">
                <i class="bi bi-plus-circle me-2"></i>Create Menu
            </button>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="rebuild_categories">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="bi bi-arrow-clockwise me-2"></i>Rebuild Category Menus
                    </button>
                </form>
                
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-trash me-2"></i>Clear Menu Cache
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Menu Locations</h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>header:</strong> Main navigation menu<br>
                    <strong>footer:</strong> Footer navigation menu<br>
                    <strong>sidebar:</strong> Sidebar navigation<br>
                    <strong>mobile:</strong> Mobile menu
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Menu List -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list me-2"></i>Navigation Menus</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($menus_result && $menus_result->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($menu = $menus_result->fetch_assoc()): ?>
                            <a href="?menu_id=<?php echo $menu['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $selected_menu_id == $menu['id'] ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($menu['name']); ?></h6>
                                        <small class="text-muted">Location: <?php echo htmlspecialchars($menu['location']); ?></small>
                                    </div>
                                    <span class="badge bg-<?php echo $menu['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($menu['status']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-menu-up display-4 text-muted"></i>
                        <p class="text-muted mt-2">No menus found</p>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createMenuModal">
                            Create Your First Menu
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Menu Items -->
    <div class="col-lg-8">
        <?php if ($selected_menu): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($selected_menu['name']); ?> Items</h5>
                        <small class="text-muted"><?php echo htmlspecialchars($selected_menu['description']); ?></small>
                    </div>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus me-2"></i>Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if ($menu_items && $menu_items->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Title</th>
                                        <th>URL</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $menu_items->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $item['sort_order']; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($item['icon_class']): ?>
                                                        <i class="<?php echo htmlspecialchars($item['icon_class']); ?> me-2"></i>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($item['title']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($item['url'] ?: '#'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($item['is_category_link']): ?>
                                                    <span class="badge bg-info">Category</span>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['category_name']); ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Custom</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($item['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_menu_item">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this menu item?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-menu-button-wide display-4 text-muted"></i>
                            <h4 class="text-muted mt-3">No menu items</h4>
                            <p class="text-muted">Add items to build your navigation menu</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="bi bi-plus me-2"></i>Add First Item
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Menu Preview -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Menu Preview</h5>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3 bg-light">
                        <?php 
                        $preview_html = $menuManager->renderMenu($selected_menu['id'], [
                            'container_class' => 'nav nav-pills',
                            'item_class' => 'nav-item',
                            'link_class' => 'nav-link',
                            'show_icons' => true
                        ]);
                        echo $preview_html ?: '<p class="text-muted mb-0">No items to preview</p>';
                        ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-menu-up display-1 text-muted"></i>
                    <h3 class="text-muted mt-3">Select a Menu</h3>
                    <p class="text-muted">Choose a menu from the list to manage its items</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Menu Modal -->
<div class="modal fade" id="createMenuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_menu">
                    
                    <div class="mb-3">
                        <label for="menu_name" class="form-label">Menu Name</label>
                        <input type="text" class="form-control" id="menu_name" name="name" required 
                               placeholder="e.g., Main Navigation">
                    </div>
                    
                    <div class="mb-3">
                        <label for="menu_location" class="form-label">Location</label>
                        <select class="form-select" id="menu_location" name="location" required>
                            <option value="">Select location...</option>
                            <option value="header">Header</option>
                            <option value="footer">Footer</option>
                            <option value="sidebar">Sidebar</option>
                            <option value="mobile">Mobile</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="menu_description" class="form-label">Description</label>
                        <textarea class="form-control" id="menu_description" name="description" rows="3" 
                                  placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Menu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Menu Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Menu Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_menu_item">
                    <input type="hidden" name="menu_id" value="<?php echo $selected_menu_id; ?>">
                    
                    <div class="mb-3">
                        <label for="item_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="item_title" name="title" required 
                               placeholder="e.g., Home, About, Contact">
                    </div>
                    
                    <div class="mb-3">
                        <label for="item_url" class="form-label">URL</label>
                        <input type="text" class="form-control" id="item_url" name="url" 
                               placeholder="e.g., /bookshelf/, /bookshelf/about.php">
                        <div class="form-text">Leave empty for dropdown parents</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_icon" class="form-label">Icon Class</label>
                                <input type="text" class="form-control" id="item_icon" name="icon_class" 
                                       placeholder="e.g., bi bi-house">
                                <div class="form-text">Bootstrap Icons class</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="item_order" name="sort_order" 
                                       value="0" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="item_parent" class="form-label">Parent Item</label>
                        <select class="form-select" id="item_parent" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php if ($selected_menu && $menu_items): ?>
                                <?php
                                $menu_items->data_seek(0); // Reset pointer
                                while ($item = $menu_items->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.list-group-item.active {
    background-color: var(--admin-primary);
    border-color: var(--admin-primary);
}

.nav-pills .nav-link {
    color: #6c757d;
    margin-right: 0.5rem;
}

.nav-pills .nav-link:hover {
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 0.25rem;
}

.table th {
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}
</style>

<script>
function editMenuItem(item) {
    // You can implement edit functionality here
    alert('Edit functionality coming soon!\nItem: ' + item.title);
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>