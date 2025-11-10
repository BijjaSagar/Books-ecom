<?php
// admin/amazon-order-manager.php - Amazon-like order management
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Order #{$order_id} status updated to " . ucfirst($new_status);
        } else {
            $_SESSION['error_message'] = "Failed to update order status.";
        }
    }
    header("Location: amazon-order-manager.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "o.order_status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ? OR o.customer_email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM orders o $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_orders = $count_result->fetch_assoc()['total'];
} else {
    $total_orders = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_orders / $limit);

// Get orders
$orders_sql = "SELECT o.*,
               COALESCE(CONCAT(o.first_name, ' ', o.last_name), 'Guest') as customer_name
               FROM orders o
               $where_clause
               ORDER BY o.created_at DESC
               LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $orders_stmt = $conn->prepare($orders_sql);
    $types = str_repeat('s', count($params));
    $orders_stmt->bind_param($types, ...$params);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
} else {
    $orders_result = $conn->query($orders_sql);
}

// Get order statistics
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')")->fetch_assoc()['total'] ?? 0;
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
$today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
$processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch_assoc()['count'];

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Amazon-like Header -->
    <div class="page-header" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📦 Order Management</h1>
                <p class="page-subtitle">Track and fulfill customer orders efficiently</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="window.print()">
                    <span>🖨️</span> Print Report
                </button>
                <a href="reports-professional.php" class="btn-professional btn-primary-professional">
                    <span>📈</span> View Reports
                </a>
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
        <div class="stat-card-professional" style="border-left-color: #146eb4;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #146eb4 0%, #232f3e 100%);">
                💰
            </div>
            <h2 class="stat-value">₹<?php echo number_format($total_revenue, 0); ?></h2>
            <p class="stat-label">Total Revenue</p>
            <div class="stat-change positive">
                <span>📈</span> From completed orders
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #ff9900;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9900 0%, #ffd700 100%);">
                🕰️
            </div>
            <h2 class="stat-value"><?php echo number_format($pending_orders); ?></h2>
            <p class="stat-label">Pending Orders</p>
            <div class="stat-change <?php echo $pending_orders > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $pending_orders > 0 ? '⚠️' : '✅'; ?></span> Needs attention
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #232f3e;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
                🔄
            </div>
            <h2 class="stat-value"><?php echo number_format($processing_orders); ?></h2>
            <p class="stat-label">Processing Orders</p>
            <div class="stat-change positive">
                <span>⚙️</span> In progress
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #28a745;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                🎆
            </div>
            <h2 class="stat-value"><?php echo number_format($today_orders); ?></h2>
            <p class="stat-label">Today's Orders</p>
            <div class="stat-change positive">
                <span>📅</span> Fresh orders
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">🔍 Search Orders</label>
                <input type="text" class="form-control-professional" name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Order number, customer name, email...">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Status Filter</label>
                <select name="status" class="form-control-professional form-select-professional">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>🕰️ Pending</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>⚙️ Processing</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>🚚 Shipped</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>✅ Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📅 Date From</label>
                <input type="date" class="form-control-professional" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📅 Date To</label>
                <input type="date" class="form-control-professional" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Filter
                    </button>
                    <a href="amazon-order-manager.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Professional Orders Table -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📊</span> Orders List
                <span class="status-badge-professional status-active" style="margin-left: var(--spacing-2);">
                    <?php echo number_format($total_orders); ?> orders
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
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-size: var(--font-size-lg);">
                                        <?php echo htmlspecialchars($order['order_number'] ?? '#' . str_pad($order['id'], 4, '0', STR_PAD_LEFT)); ?>
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                        <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-3);">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--font-size-sm);">
                                            <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <div style="font-size: var(--font-size-xs); color: var(--gray-500);"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--gray-700);">
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                        <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    // Get order items count
                                    $items_stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
                                    $items_stmt->bind_param("i", $order['id']);
                                    $items_stmt->execute();
                                    $items_result = $items_stmt->get_result();
                                    $items_count = $items_result->fetch_assoc()['count'];
                                    ?>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-2);">
                                        <span style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%); color: white; padding: 4px 8px; border-radius: 12px; font-size: var(--font-size-xs); font-weight: 600;">
                                            <?php echo $items_count; ?> item<?php echo $items_count != 1 ? 's' : ''; ?>
                                        </span>
                                        <span>📦</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: var(--font-size-lg); color: var(--success);">
                                        ₹<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-500);">
                                        <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <?php
                                        $status_options = [
                                            'pending' => ['🕰️', 'Pending', 'status-pending'],
                                            'processing' => ['⚙️', 'Processing', 'status-processing'],
                                            'shipped' => ['🚚', 'Shipped', 'status-shipped'],
                                            'delivered' => ['✅', 'Delivered', 'status-delivered'],
                                            'cancelled' => ['❌', 'Cancelled', 'status-cancelled']
                                        ];
                                        $current_status = $order['order_status'] ?? 'pending';
                                        $current_config = $status_options[$current_status];
                                        ?>
                                        <select name="status" class="form-control-professional form-select-professional status-badge-professional <?php echo $current_config[2]; ?>" 
                                                onchange="updateOrderStatus(this)" style="min-width: 140px; font-size: var(--font-size-xs);">
                                            <?php foreach ($status_options as $value => $config): ?>
                                                <option value="<?php echo $value; ?>" <?php echo $current_status === $value ? 'selected' : ''; ?>>
                                                    <?php echo $config[0] . ' ' . $config[1]; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-1);">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-professional btn-info-professional" 
                                           style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                           title="View Order Details">
                                            <span>👁️</span> View
                                        </a>
                                        <button type="button" 
                                                class="btn-professional btn-outline-professional" 
                                                style="padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" 
                                                onclick="printOrder(<?php echo $order['id']; ?>)" 
                                                title="Print Order">
                                            <span>🖨️</span> Print
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: var(--spacing-12);">
                                <div style="color: var(--gray-500);">
                                    <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">📬</div>
                                    <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No orders found</h4>
                                    <p style="margin: 0;">Orders will appear here once customers start purchasing</p>
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
                        <a class="pagination-btn" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <span>←</span> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a class="pagination-btn" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                            Next <span>→</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance Insights -->
    <div class="professional-card" style="margin-top: var(--spacing-6);">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>💡</span> Order Management Tips
            </h3>
        </div>
        <div class="card-body-professional">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>⏱️</span> Fulfillment Best Practices
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <div style="padding: var(--spacing-4); background: #dbeafe; border-radius: var(--border-radius-sm); border-left: 4px solid var(--info);">
                            <div style="font-weight: 600;">Ship Within 24 Hours</div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Orders shipped within 24 hours have a 95% customer satisfaction rate.
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #d1fae5; border-radius: var(--border-radius-sm); border-left: 4px solid var(--success);">
                            <div style="font-weight: 600;">Provide Tracking Information</div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Customers with tracking information are 80% less likely to contact support.
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>📞</span> Customer Communication
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <div style="padding: var(--spacing-4); background: #fef3c7; border-radius: var(--border-radius-sm); border-left: 4px solid var(--warning);">
                            <div style="font-weight: 600;">Proactive Notifications</div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Send shipping notifications to reduce customer inquiries by up to 60%.
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #e0e7ff; border-radius: var(--border-radius-sm); border-left: 4px solid var(--primary);">
                            <div style="font-weight: 600;">Handle Returns Promptly</div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Process returns within 48 hours to maintain seller rating.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Professional JavaScript -->
<script>
function printOrder(orderId) {
    const printWindow = window.open(`order-details.php?id=${orderId}&print=1`, '_blank');
    printWindow.onload = function() {
        printWindow.print();
    };
}

function updateOrderStatus(selectElement) {
    const newStatus = selectElement.value;
    const form = selectElement.closest('form');
    const orderId = form.querySelector('input[name="order_id"]').value;
    
    // Show confirmation for critical status changes
    if (newStatus === 'cancelled') {
        if (!confirm(`⚠️ Are you sure you want to cancel order #${orderId}?\n\nThis action cannot be undone and the customer will be notified.`)) {
            selectElement.value = selectElement.getAttribute('data-original-value');
            return false;
        }
    } else if (newStatus === 'delivered') {
        if (!confirm(`✅ Mark order #${orderId} as delivered?\n\nThis will complete the order and notify the customer.`)) {
            selectElement.value = selectElement.getAttribute('data-original-value');
            return false;
        }
    }
    
    // Show loading state
    selectElement.disabled = true;
    selectElement.style.opacity = '0.7';
    
    // Add loading indicator
    const originalHTML = selectElement.outerHTML;
    selectElement.style.background = 'var(--gray-200)';
    
    // Submit form
    form.submit();
}

// Professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Store original values for status selects
    document.querySelectorAll('select[name="status"]').forEach(select => {
        select.setAttribute('data-original-value', select.value);
    });
    
    // Animate table rows
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
                this.style.borderColor = 'var(--primary)';
                setTimeout(() => {
                    this.style.borderColor = 'var(--gray-300)';
                }, 500);
            }, 300);
        });
    }
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>