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
    header("Location: customers-professional.php");
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

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$admin_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$customer_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">👥 Customer Management</h1>
                <p class="page-subtitle">Manage customer accounts and permissions</p>
            </div>
            <button class="btn-professional btn-primary-professional" onclick="openAddCustomerModal()">
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

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">👥</div>
            <h2 class="stat-value"><?php echo number_format($total_users); ?></h2>
            <p class="stat-label">Total Users</p>
            <div class="stat-change positive">
                <span>📈</span> All accounts
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                👤
            </div>
            <h2 class="stat-value"><?php echo number_format($customer_users); ?></h2>
            <p class="stat-label">Customers</p>
            <div class="stat-change positive">
                <span>🛒</span> Active buyers
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                🔧
            </div>
            <h2 class="stat-value"><?php echo number_format($admin_users); ?></h2>
            <p class="stat-label">Admin Users</p>
            <div class="stat-change">
                <span>🔒</span> System access
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                🆕
            </div>
            <h2 class="stat-value"><?php echo number_format($new_users); ?></h2>
            <p class="stat-label">New Users (7d)</p>
            <div class="stat-change positive">
                <span>📅</span> Recent signups
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Customers</label>
                <input type="text" class="form-control-professional" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Name or email...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">👤 Role Filter</label>
                <select name="role" class="form-control-professional form-select-professional">
                    <option value="">All Roles</option>
                    <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>👤 Customer</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>🔧 Admin</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Search
                    </button>
                    <a href="customers-professional.php" class="btn-professional btn-outline-professional">
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
            <div style="color: var(--gray-600); font-size: var(--font-size-sm);">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </div>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <table class="table-professional">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Info</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                        <?php while($customer = $customers_result->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td>
                                    <span style="font-weight: 600; color: var(--primary);">#<?php echo $customer['id']; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-3);">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--font-size-sm);">
                                            <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500);">User ID: <?php echo $customer['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-2);">
                                        <span>📧</span>
                                        <span style="color: var(--gray-700);"><?php echo htmlspecialchars($customer['email']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $role = $customer['role'];
                                    $roleLabel = $role === 'admin' ? '🔧 Admin' : '👤 Customer';
                                    $roleClass = $role === 'admin' ? 'status-warning' : 'status-success';
                                    ?>
                                    <span class="status-badge-professional <?php echo $roleClass; ?>">
                                        <?php echo $roleLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="color: var(--gray-600);">
                                        <div style="font-weight: 500;"><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></div>
                                        <div style="font-size: var(--font-size-xs);"><?php echo date('g:i A', strtotime($customer['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-1);">
                                        <?php if ($customer['role'] !== 'admin'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <select name="role" class="form-control-professional form-select-professional" 
                                                        onchange="this.form.submit()" style="font-size: var(--font-size-xs); min-width: 100px;">
                                                    <option value="customer" <?php echo $customer['role'] === 'customer' ? 'selected' : ''; ?>>👤 Customer</option>
                                                    <option value="admin" <?php echo $customer['role'] === 'admin' ? 'selected' : ''; ?>>🔧 Admin</option>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs); background: var(--gray-200); color: var(--gray-600); border-radius: 6px;">
                                                🔧 Admin
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($customer['id'] != $_SESSION['admin_id'] && $customer['role'] !== 'admin'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <button type="submit" class="btn-professional btn-danger-professional" 
                                                        style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                        title="Delete Customer">
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
                            <td colspan="6" style="text-align: center; padding: var(--spacing-12);">
                                <div style="color: var(--gray-500);">
                                    <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">👥</div>
                                    <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No customers found</h4>
                                    <p style="margin: 0;">Start by adding your first customer account</p>
                                    <button class="btn-professional btn-primary-professional" style="margin-top: var(--spacing-4);" onclick="openAddCustomerModal()">
                                        <span>➕</span> Add First Customer
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
                        <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>">
                            <span>←</span> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
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
            </div>
        <?php endif; ?>
    </div>

    <!-- Professional Add Customer Modal -->
    <div class="modal-professional" id="addCustomerModal">
        <div class="modal-content-professional">
            <div class="modal-header-professional">
                <h3 style="margin: 0; display: flex; align-items: center; gap: var(--spacing-2);">
                    <span>➕</span> Add New Customer
                </h3>
                <button type="button" class="btn-professional" style="background: none; border: none; font-size: 1.5rem; padding: var(--spacing-1);" onclick="closeModal('addCustomerModal')">
                    <span>❌</span>
                </button>
            </div>
            <form method="POST" class="customer-form">
                <div class="modal-body-professional">
                    <input type="hidden" name="action" value="add_customer">
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">👤 Full Name</label>
                        <input type="text" class="form-control-professional" name="full_name" required 
                               placeholder="Enter full name...">
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">📧 Email Address</label>
                        <input type="email" class="form-control-professional" name="email" required 
                               placeholder="Enter email address...">
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">🔒 Password</label>
                        <input type="password" class="form-control-professional" name="password" required 
                               placeholder="Enter password..." minlength="6">
                    </div>
                    
                    <div class="form-group-professional">
                        <label class="form-label-professional">👤 Role</label>
                        <select class="form-control-professional form-select-professional" name="role">
                            <option value="customer">👤 Customer</option>
                            <option value="admin">🔧 Admin</option>
                        </select>
                    </div>
                    
                    <div class="alert-professional alert-info-professional">
                        <span>ℹ️</span>
                        <span>Customers can browse and purchase products. Admins have full access to the admin panel.</span>
                    </div>
                </div>
                <div class="modal-footer-professional">
                    <button type="button" class="btn-professional btn-outline-professional" onclick="closeModal('addCustomerModal')">
                        <span>❌</span> Cancel
                    </button>
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>💾</span> Add Customer
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Professional JavaScript -->
<script>
function openAddCustomerModal() {
    openModal('addCustomerModal');
}

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add table animations and form enhancements
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>