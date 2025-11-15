<?php
/**
 * Admin Reports and Analytics
 * Comprehensive business analytics with date range filtering
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';
require_once '../includes/ExportManager.php';

$export_manager = new ExportManager($conn);

// Get date range from request or default to today
$date_range = $_GET['date_range'] ?? 'today';
$custom_start = $_GET['custom_start'] ?? '';
$custom_end = $_GET['custom_end'] ?? '';

// Calculate date range
$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

switch ($date_range) {
    case 'today':
        $start_date = $end_date = date('Y-m-d');
        $period_label = 'Today';
        break;
    case '7_days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        $period_label = 'Last 7 Days';
        break;
    case '30_days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        $period_label = 'Last 30 Days';
        break;
    case '90_days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        $end_date = date('Y-m-d');
        $period_label = 'Last 90 Days';
        break;
    case 'custom':
        $start_date = $custom_start ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $custom_end ?: date('Y-m-d');
        $period_label = date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date));
        break;
    default:
        $period_label = 'All Time';
}

// Get key metrics
$metrics_query = "SELECT
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    COUNT(DISTINCT user_id) as unique_customers,
    COUNT(CASE WHEN order_status = 'completed' OR order_status = 'delivered' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'";

$metrics = $conn->prepare($metrics_query);
$metrics->bind_param('ss', $start_date, $end_date);
$metrics->execute();
$metrics_data = $metrics->get_result()->fetch_assoc();
$metrics->close();

// Get new customers in period
$new_customers_query = "SELECT COUNT(*) as new_customers FROM users WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?";
$new_cust = $conn->prepare($new_customers_query);
$new_cust->bind_param('ss', $start_date, $end_date);
$new_cust->execute();
$new_cust_data = $new_cust->get_result()->fetch_assoc();
$new_cust->close();

// Get top products
$top_products_query = "SELECT
    p.id,
    p.title,
    p.author,
    COUNT(oi.id) as units_sold,
    SUM(oi.quantity * oi.price) as revenue,
    AVG(p.rating) as avg_rating
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? OR DATE(o.created_at) IS NULL
    GROUP BY p.id
    ORDER BY units_sold DESC
    LIMIT 10";

$top_products = $conn->prepare($top_products_query);
$top_products->bind_param('ss', $start_date, $end_date);
$top_products->execute();
$top_products_result = $top_products->get_result();

// Get category performance
$category_query = "SELECT
    c.id,
    c.name,
    COUNT(oi.id) as items_sold,
    SUM(oi.quantity * oi.price) as revenue,
    AVG(p.rating) as avg_rating
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ? OR DATE(o.created_at) IS NULL
    GROUP BY c.id
    ORDER BY revenue DESC";

$categories = $conn->prepare($category_query);
$categories->bind_param('ss', $start_date, $end_date);
$categories->execute();
$categories_result = $categories->get_result();

// Get order status distribution
$status_query = "SELECT
    order_status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?), 1) as percentage
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY order_status
    ORDER BY count DESC";

$status_stmt = $conn->prepare($status_query);
$status_stmt->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$status_stmt->execute();
$status_result = $status_stmt->get_result();

// Get payment method distribution
$payment_query = "SELECT
    payment_method,
    COUNT(*) as count,
    SUM(total_amount) as total
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total DESC";

$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param('ss', $start_date, $end_date);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

// Get daily sales trend
$daily_query = "SELECT
    DATE(created_at) as sale_date,
    COUNT(*) as orders,
    SUM(total_amount) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC";

$daily_stmt = $conn->prepare($daily_query);
$daily_stmt->bind_param('ss', $start_date, $end_date);
$daily_stmt->execute();
$daily_result = $daily_stmt->get_result();

// Calculate averages for period
$period_days = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;
$avg_daily_revenue = $metrics_data['total_revenue'] / $period_days;
$avg_daily_orders = $metrics_data['total_orders'] / $period_days;
?>

<style>
    :root {
        --primary: #1e40af;
        --primary-dark: #1e3a8a;
        --secondary: #3b82f6;
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

    .filter-section {
        background: white;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .filter-group {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-group button {
        padding: 10px 16px;
        border: 2px solid var(--border-color);
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        color: var(--text-primary);
    }

    .filter-group button.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .filter-group button:hover {
        border-color: var(--primary);
    }

    .filter-group input {
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
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

    .stat-change {
        font-size: 0.9rem;
        color: var(--text-secondary);
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 32px 0 20px 0;
        color: var(--text-primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: 12px;
    }

    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .report-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .report-card h3 {
        margin: 0 0 16px 0;
        color: var(--primary);
        font-size: 1.25rem;
    }

    .status-distribution {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .status-bar {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .status-label {
        width: 100px;
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.9rem;
    }

    .status-bar-container {
        flex: 1;
        height: 24px;
        background: #f3f4f6;
        border-radius: 4px;
        overflow: hidden;
    }

    .status-bar-fill {
        height: 100%;
        background: var(--primary);
        transition: width 0.3s ease;
    }

    .status-bar.pending .status-bar-fill {
        background: var(--warning);
    }

    .status-bar.success .status-bar-fill {
        background: var(--success);
    }

    .status-bar.danger .status-bar-fill {
        background: var(--danger);
    }

    .status-percent {
        width: 50px;
        text-align: right;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 0.9rem;
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

    .rating {
        color: #fbbf24;
        font-size: 1.1rem;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .filter-group {
            flex-direction: column;
        }

        .filter-group button,
        .filter-group input {
            width: 100%;
        }

        .reports-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>📊 Business Reports & Analytics</h1>
        <p>Comprehensive business insights and performance metrics</p>
    </div>
</div>

<div class="container">
    <!-- Date Range Filter -->
    <div class="filter-section">
        <div class="filter-group">
            <button class="<?php echo $date_range === 'today' ? 'active' : ''; ?>" onclick="setDateRange('today')">Today</button>
            <button class="<?php echo $date_range === '7_days' ? 'active' : ''; ?>" onclick="setDateRange('7_days')">Last 7 Days</button>
            <button class="<?php echo $date_range === '30_days' ? 'active' : ''; ?>" onclick="setDateRange('30_days')">Last 30 Days</button>
            <button class="<?php echo $date_range === '90_days' ? 'active' : ''; ?>" onclick="setDateRange('90_days')">Last 90 Days</button>
            <button class="<?php echo $date_range === 'custom' ? 'active' : ''; ?>" onclick="toggleCustomDate()">Custom Range</button>
        </div>
        <div id="customDateFields" style="display: <?php echo $date_range === 'custom' ? 'flex' : 'none'; ?>; gap: 12px; margin-top: 12px; flex-wrap: wrap;">
            <input type="date" id="startDate" value="<?php echo $custom_start ?: date('Y-m-d', strtotime('-30 days')); ?>">
            <input type="date" id="endDate" value="<?php echo $custom_end ?: date('Y-m-d'); ?>">
            <button class="filter-group button" style="width: auto;" onclick="applyCustomDate()">Apply</button>
        </div>
        <p style="margin: 12px 0 0 0; color: var(--text-secondary);"><strong>Period:</strong> <?php echo $period_label; ?> (<?php echo $period_days; ?> days)</p>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">₹<?php echo number_format($metrics_data['total_revenue'] ?? 0, 0); ?></div>
            <div class="stat-change">Avg Daily: ₹<?php echo number_format($avg_daily_revenue, 0); ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?php echo $metrics_data['total_orders'] ?? 0; ?></div>
            <div class="stat-change">Avg Daily: <?php echo round($avg_daily_orders, 1); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Average Order Value</div>
            <div class="stat-value">₹<?php echo number_format($metrics_data['avg_order_value'] ?? 0, 0); ?></div>
            <div class="stat-change"><?php echo $metrics_data['completed_orders']; ?> Completed</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">Unique Customers</div>
            <div class="stat-value"><?php echo $metrics_data['unique_customers'] ?? 0; ?></div>
            <div class="stat-change"><?php echo $new_cust_data['new_customers']; ?> New Customers</div>
        </div>
    </div>

    <!-- Order Status Distribution -->
    <h2 class="section-title">Order Status Distribution</h2>
    <div class="reports-grid">
        <div class="report-card">
            <h3>Status Breakdown</h3>
            <div class="status-distribution">
                <?php
                $status_colors = [
                    'pending' => 'warning',
                    'processing' => 'primary',
                    'shipped' => 'primary',
                    'delivered' => 'success',
                    'cancelled' => 'danger'
                ];
                while ($status = $status_result->fetch_assoc()):
                ?>
                    <div class="status-bar <?php echo $status_colors[$status['order_status']] ?? 'primary'; ?>">
                        <div class="status-label"><?php echo ucfirst($status['order_status']); ?></div>
                        <div class="status-bar-container">
                            <div class="status-bar-fill" style="width: <?php echo $status['percentage']; ?>%"></div>
                        </div>
                        <div class="status-percent"><?php echo $status['percentage']; ?>%</div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="report-card">
            <h3>Payment Method Distribution</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($payment_result->num_rows > 0):
                            while ($payment = $payment_result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo ucfirst($payment['payment_method'] ?? 'Unknown'); ?></td>
                                <td><?php echo $payment['count']; ?></td>
                                <td><strong>₹<?php echo number_format($payment['total'] ?? 0, 0); ?></strong></td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-secondary);">No payment data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <h2 class="section-title">Top Selling Products</h2>
    <div class="report-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Author</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($top_products_result->num_rows > 0):
                        while ($product = $top_products_result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['author'] ?? 'Unknown'); ?></td>
                            <td><?php echo $product['units_sold'] ?? 0; ?></td>
                            <td><strong>₹<?php echo number_format($product['revenue'] ?? 0, 0); ?></strong></td>
                            <td>
                                <?php if ($product['avg_rating']): ?>
                                    <span class="rating">★ <?php echo round($product['avg_rating'], 1); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">No ratings</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-secondary);">No product data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Performance -->
    <h2 class="section-title">Category Performance</h2>
    <div class="report-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Items Sold</th>
                        <th>Revenue</th>
                        <th>Avg Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($categories_result->num_rows > 0):
                        while ($category = $categories_result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                            <td><?php echo $category['items_sold'] ?? 0; ?></td>
                            <td><strong>₹<?php echo number_format($category['revenue'] ?? 0, 0); ?></strong></td>
                            <td>
                                <?php if ($category['avg_rating']): ?>
                                    <span class="rating">★ <?php echo round($category['avg_rating'], 1); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">No ratings</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-secondary);">No category data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function setDateRange(range) {
        const url = new URL(window.location);
        url.searchParams.set('date_range', range);
        window.location.href = url.toString();
    }

    function toggleCustomDate() {
        const customFields = document.getElementById('customDateFields');
        if (customFields.style.display === 'none') {
            customFields.style.display = 'flex';
        } else {
            customFields.style.display = 'none';
        }
    }

    function applyCustomDate() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;

        if (!startDate || !endDate) {
            alert('Please select both start and end dates');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            alert('Start date must be before end date');
            return;
        }

        const url = new URL(window.location);
        url.searchParams.set('date_range', 'custom');
        url.searchParams.set('custom_start', startDate);
        url.searchParams.set('custom_end', endDate);
        window.location.href = url.toString();
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
