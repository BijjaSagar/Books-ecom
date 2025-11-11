<?php
/**
 * Admin Customer Management
 * View, search, and manage customer accounts
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';

$message = '';
$message_type = '';

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'block_customer') {
        $customer_id = intval($_POST['customer_id']);
        $update = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ? AND role = 'customer'");
        $update->bind_param('i', $customer_id);
        if ($update->execute()) {
            $message = 'Customer blocked successfully!';
            $message_type = 'success';
        }
        $update->close();
    } elseif ($_POST['action'] === 'unblock_customer') {
        $customer_id = intval($_POST['customer_id']);
        $update = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'customer'");
        $update->bind_param('i', $customer_id);
        if ($update->execute()) {
            $message = 'Customer unblocked successfully!';
            $message_type = 'success';
        }
        $update->close();
    }
}

// Get search/filter parameters
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

// Build customer query
$query = "
    SELECT 
        u.id, u.name, u.email, u.phone, u.created_at, u.status,
        COUNT(o.id) as total_orders,
        SUM(o.total_amount) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
";

$types = '';
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_term = '%' . $search . '%';
    $types = 'sss';
    $params = [$search_term, $search_term, $search_term];
}

$query .= " GROUP BY u.id";

// Apply sorting
switch ($sort) {
    case 'newest':
        $query .= " ORDER BY u.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY u.created_at ASC";
        break;
    case 'spending_high':
        $query .= " ORDER BY total_spent DESC";
        break;
    case 'spending_low':
        $query .= " ORDER BY total_spent ASC";
        break;
    default:
        $query .= " ORDER BY u.created_at DESC";
}

$query .= " LIMIT 100";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get customer statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked_customers,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
    FROM users
    WHERE role = 'customer'
";
$stats = $conn->prepare($stats_query);
$stats->execute();
$stats_data = $stats->get_result()->fetch_assoc();
$stats->close();
?>

<style>
    :root {
        --primary: #1e40af;
        --primary-dark: #1e3a8a;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --border-color: #e5e7eb;
        --text-primary: #374151;
        --text-secondary: #6b7280;
    }

    .page-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        padding: 32px 0;
        margin-bottom: 32px;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--primary);
    }

    .stat-card.success {
        border-left-color: var(--success);
    }

    .stat-card.warning {
        border-left-color: var(--warning);
    }

    .stat-card.danger {
        border-left-color: var(--danger);
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .stat-card.success .stat-value {
        color: var(--success);
    }

    .stat-card.warning .stat-value {
        color: var(--warning);
    }

    .stat-card.danger .stat-value {
        color: var(--danger);
    }

    .card-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 32px;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 20px 0;
        color: var(--primary);
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border-color);
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

    .filter-section {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-input {
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
        flex: 1;
        min-width: 200px;
    }

    .sort-select {
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: white;
    }

    .search-btn {
        padding: 10px 20px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .search-btn:hover {
        background: var(--primary-dark);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f3f4f6;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
        font-size: 0.9rem;
    }

    .data-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .data-table tr:hover {
        background: #f9fafb;
    }

    .customer-name {
        font-weight: 600;
        color: var(--primary);
    }

    .customer-email {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
    }

    .status-blocked {
        background: #fee2e2;
        color: #991b1b;
    }

    .action-btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
        margin-right: 4px;
    }

    .action-btn-primary {
        background: var(--primary);
        color: white;
    }

    .action-btn-primary:hover {
        background: var(--primary-dark);
    }

    .action-btn-danger {
        background: var(--danger);
        color: white;
    }

    .action-btn-danger:hover {
        background: #dc2626;
    }

    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        border: none;
    }

    @media (max-width: 768px) {
        .filter-section {
            flex-direction: column;
        }

        .search-input,
        .sort-select {
            width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .data-table {
            font-size: 0.85rem;
        }

        .data-table th,
        .data-table td {
            padding: 8px;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>👥 Customer Management</h1>
        <p>View and manage all customer accounts</p>
    </div>
</div>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Customers</div>
            <div class="stat-value"><?php echo $stats_data['total_customers']; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Active Customers</div>
            <div class="stat-value"><?php echo $stats_data['active_customers']; ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Blocked Customers</div>
            <div class="stat-value"><?php echo $stats_data['blocked_customers']; ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">New Today</div>
            <div class="stat-value"><?php echo $stats_data['new_today']; ?></div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="card-section">
        <h3 class="section-title">📊 All Customers</h3>

        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" class="search-input" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="sort" class="sort-select">
                    <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest Joined</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="spending_high" <?php echo $sort === 'spending_high' ? 'selected' : ''; ?>>High Spenders</option>
                    <option value="spending_low" <?php echo $sort === 'spending_low' ? 'selected' : ''; ?>>Low Spenders</option>
                </select>

                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></div>
                                    <div class="customer-email">#<?php echo $customer['id']; ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($customer['email']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <strong><?php echo $customer['total_orders'] ?? 0; ?></strong>
                                </td>
                                <td>
                                    <strong>₹<?php echo number_format($customer['total_spent'] ?? 0, 0); ?></strong>
                                </td>
                                <td>
                                    <?php if ($customer['last_order_date']): ?>
                                        <span style="font-size: 0.9rem;"><?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $customer['status']; ?>">
                                        <?php echo $customer['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                        <?php if ($customer['status'] === 'active'): ?>
                                            <button type="submit" name="action" value="block_customer" class="action-btn action-btn-danger" onclick="return confirm('Block this customer?');">Block</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="unblock_customer" class="action-btn action-btn-primary">Unblock</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                No customers found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/admin_footer.php'; ?>
