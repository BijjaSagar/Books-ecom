<?php
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $customer_id = intval($_POST['customer_id']);
        
        switch ($action) {
            case 'delete':
                // Prevent deleting admin accounts
                $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $check_stmt->bind_param("i", $customer_id);
                $check_stmt->execute();
                $user_role = $check_stmt->get_result()->fetch_assoc()['role'] ?? '';
                
                if ($user_role !== 'admin' && $customer_id != $_SESSION['admin_id']) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                    $stmt->bind_param("i", $customer_id);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Customer deleted successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to delete customer.";
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = "Cannot delete admin accounts.";
                }
                break;
                
            case 'change_role':
                $new_role = $_POST['role'];
                $allowed_roles = ['customer', 'admin'];
                
                if (in_array($new_role, $allowed_roles) && $customer_id != $_SESSION['admin_id']) {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_role, $customer_id);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "User role updated successfully.";
                    } else {
                        $_SESSION['error_message'] = "Failed to update user role.";
                    }
                    $stmt->close();
                }
                break;
                
            case 'add_customer':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'] ?? 'customer';
                
                // Validate inputs
                if (empty($full_name) || empty($email) || empty($password)) {
                    $_SESSION['error_message'] = "All fields are required.";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error_message'] = "Invalid email format.";
                } else {
                    // Check if email already exists
                    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    
                    if ($check_stmt->get_result()->num_rows > 0) {
                        $_SESSION['error_message'] = "Email already exists.";
                    } else {
                        // Add new customer
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Customer added successfully.";
                        } else {
                            $_SESSION['error_message'] = "Failed to add customer.";
                        }
                        $stmt->close();
                    }
                    $check_stmt->close();
                }
                break;
        }
    }
    header("Location: customers.php");
    exit();
}

// Pagination and search
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_customers = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_customers = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_customers / $limit);

// Get customers
$customers_sql = "SELECT id, full_name, email, role, created_at FROM users 
                  $where_clause 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";

$final_params = array_merge($params, [$limit, $offset]);
$final_types = $types . "ii";

$customers_stmt = $conn->prepare($customers_sql);
$customers_stmt->bind_param($final_types, ...$final_params);
$customers_stmt->execute();
$customers_result = $customers_stmt->get_result();

// Inject professional CSS
injectProfessionalCSS();
renderProfessionalJavaScript();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">👥 Customer Management</h1>
                <p class="page-subtitle">Manage customer accounts and permissions</p>
            </div>
            <button class="btn-professional btn-primary-professional" onclick="openModal('addCustomerModal')">
                <span>➕</span> Add Customer
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <?php renderProfessionalAlert('success', $_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <?php renderProfessionalAlert('error', $_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Customers</label>
                <input type="text" class="form-control-professional" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or email...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Role Filter</label>
                <select name="role" class="form-control-professional form-select-professional">
                    <option value="">All Roles</option>
                    <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Search
                    </button>
                    <a href="customers.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Professional Customers Table -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📋</span> Customers List
                <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                    <?php echo number_format($total_customers); ?> total
                </span>
            </h3>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <div class="table-responsive">
                <table class="table-professional">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Role</th>
                            <th>Registered</th>
                            <th>Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <?php
                                // Get order count for this customer
                                $order_count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_email = ?");
                                $order_count_stmt->bind_param("s", $customer['email']);
                                $order_count_stmt->execute();
                                $order_count = $order_count_stmt->get_result()->fetch_assoc()['count'];
                                ?>
                                <tr class="fade-in">
                                    <td><?php echo $customer['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">
                                                <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <select name="role" class="form-control-professional form-select-professional" 
                                                    onchange="this.form.submit()" 
                                                    style="min-width: 120px;"
                                                    <?php echo $customer['id'] == $_SESSION['admin_id'] ? 'disabled' : ''; ?>>
                                                <option value="customer" <?php echo $customer['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                <option value="admin" <?php echo $customer['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge-professional status-info">
                                            <?php echo $order_count; ?> orders
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn-professional btn-info-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);">
                                                <span>👁️</span> View
                                            </a>
                                            <?php if ($customer['id'] != $_SESSION['admin_id'] && $customer['role'] !== 'admin'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" class="btn-professional btn-danger-professional" style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                            onclick="return confirm('Are you sure you want to delete this customer?')">
                                                        <span>🗑️</span> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                                        No customers found
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Professional Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-professional">
            <?php if ($page > 1): ?>
                <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                    <span>←</span> Previous
                </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a class="pagination-btn" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                    Next <span>→</span>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Professional Add Customer Modal -->
<div class="modal-professional" id="addCustomerModal">
    <div class="modal-content-professional">
        <div class="modal-header-professional">
            <h5 class="modal-title">➕ Add New Customer</h5>
            <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('addCustomerModal')">
                <span>❌</span>
            </button>
        </div>
        <form method="POST" class="form-professional">
            <div class="modal-body-professional">
                <input type="hidden" name="action" value="add_customer">
                
                <div class="form-group-professional">
                    <label for="full_name" class="form-label-professional">👤 Full Name</label>
                    <input type="text" class="form-control-professional" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group-professional">
                    <label for="email" class="form-label-professional">📧 Email</label>
                    <input type="email" class="form-control-professional" id="email" name="email" required>
                </div>
                
                <div class="form-group-professional">
                    <label for="password" class="form-label-professional">🔒 Password</label>
                    <input type="password" class="form-control-professional" id="password" name="password" required minlength="6">
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="form-group-professional">
                    <label for="role" class="form-label-professional">📊 Role</label>
                    <select class="form-control-professional form-select-professional" id="role" name="role">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer-professional">
                <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('addCustomerModal')">Cancel</button>
                <button type="submit" class="btn-professional btn-primary-professional">Add Customer</button>
            </div>
        </form>
    </div>
</div>