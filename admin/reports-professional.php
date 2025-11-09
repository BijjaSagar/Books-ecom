<?php
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Get date range from filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'overview';

// Validate dates
$start_date = date('Y-m-d', strtotime($start_date));
$end_date = date('Y-m-d', strtotime($end_date));

// Sales Overview
$sales_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    SUM(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue,
    COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?";

$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("ss", $start_date, $end_date);
$sales_stmt->execute();
$sales_data = $sales_stmt->get_result()->fetch_assoc();

// Daily sales for chart
$daily_sales_query = "SELECT 
    DATE(created_at) as date,
    COUNT(*) as orders,
    SUM(total_amount) as revenue
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC";

$daily_stmt = $conn->prepare($daily_sales_query);
$daily_stmt->bind_param("ss", $start_date, $end_date);
$daily_stmt->execute();
$daily_data = $daily_stmt->get_result();

$chart_data = [];
while ($row = $daily_data->fetch_assoc()) {
    $chart_data[] = $row;
}

// Top selling products
$top_products_query = "SELECT 
    p.title as name,
    p.price,
    SUM(oi.quantity) as total_sold,
    SUM(oi.quantity * oi.price) as revenue,
    AVG(oi.price) as avg_price
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.order_status IN ('completed', 'delivered')
    GROUP BY p.id
    HAVING total_sold > 0
    ORDER BY total_sold DESC
    LIMIT 10";

$top_products_stmt = $conn->prepare($top_products_query);
$top_products_stmt->bind_param("ss", $start_date, $end_date);
$top_products_stmt->execute();
$top_products = $top_products_stmt->get_result();

// Category performance
$category_query = "SELECT 
    c.name as category_name,
    COUNT(DISTINCT p.id) as products_count,
    SUM(oi.quantity) as total_sold,
    SUM(oi.quantity * oi.price) as revenue
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.order_status IN ('completed', 'delivered')
    GROUP BY c.id
    HAVING total_sold > 0
    ORDER BY revenue DESC";

$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("ss", $start_date, $end_date);
$category_stmt->execute();
$category_data = $category_stmt->get_result();

// Customer analytics
$customer_query = "SELECT 
    COUNT(DISTINCT CASE WHEN o.customer_email IS NOT NULL THEN o.customer_email END) as unique_customers,
    COUNT(DISTINCT CASE WHEN u.id IS NOT NULL THEN u.id END) as registered_customers,
    AVG(customer_orders.order_count) as avg_orders_per_customer
    FROM orders o
    LEFT JOIN users u ON o.customer_email = u.email
    LEFT JOIN (
        SELECT customer_email, COUNT(*) as order_count
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY customer_email
    ) customer_orders ON o.customer_email = customer_orders.customer_email
    WHERE DATE(o.created_at) BETWEEN ? AND ?";

$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$customer_stmt->execute();
$customer_analytics = $customer_stmt->get_result()->fetch_assoc();

// Top customers
$top_customers_query = "SELECT 
    COALESCE(CONCAT(o.first_name, ' ', o.last_name), 'Guest') as customer_name,
    o.customer_email,
    COUNT(*) as total_orders,
    SUM(o.total_amount) as total_spent,
    AVG(o.total_amount) as avg_order_value,
    MAX(o.created_at) as last_order
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.order_status IN ('completed', 'delivered')
    GROUP BY o.customer_email
    ORDER BY total_spent DESC
    LIMIT 10";

$top_customers_stmt = $conn->prepare($top_customers_query);
$top_customers_stmt->bind_param("ss", $start_date, $end_date);
$top_customers_stmt->execute();
$top_customers = $top_customers_stmt->get_result();

// Monthly comparison (current vs previous period)
$period_diff = (strtotime($end_date) - strtotime($start_date)) / 86400; // days
$prev_start = date('Y-m-d', strtotime($start_date . " -{$period_diff} days"));
$prev_end = date('Y-m-d', strtotime($start_date . " -1 day"));

$prev_sales_stmt = $conn->prepare($sales_query);
$prev_sales_stmt->bind_param("ss", $prev_start, $prev_end);
$prev_sales_stmt->execute();
$prev_sales_data = $prev_sales_stmt->get_result()->fetch_assoc();

// Calculate growth percentages
$revenue_growth = 0;
if ($prev_sales_data['total_revenue'] > 0) {
    $revenue_growth = (($sales_data['total_revenue'] - $prev_sales_data['total_revenue']) / $prev_sales_data['total_revenue']) * 100;
}

$order_growth = 0;
if ($prev_sales_data['total_orders'] > 0) {
    $order_growth = (($sales_data['total_orders'] - $prev_sales_data['total_orders']) / $prev_sales_data['total_orders']) * 100;
}

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📊 Reports & Analytics</h1>
                <p class="page-subtitle">Business insights and performance metrics</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="exportReport()">
                    <span>📥</span> Export Report
                </button>
                <button class="btn-professional btn-outline-professional" onclick="printReport()">
                    <span>🖨️</span> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Professional Filters -->
    <div class="filters-professional">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">📅 Start Date</label>
                <input type="date" class="form-control-professional" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📅 End Date</label>
                <input type="date" class="form-control-professional" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">📊 Report Type</label>
                <select name="report_type" class="form-control-professional form-select-professional">
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>📈 Overview</option>
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>💰 Sales</option>
                    <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>📚 Products</option>
                    <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>👥 Customers</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Generate Report
                    </button>
                    <a href="reports-professional.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Metrics Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">💰</div>
            <h2 class="stat-value"><?php echo formatCurrency($sales_data['total_revenue'] ?? 0); ?></h2>
            <p class="stat-label">Total Revenue</p>
            <div class="stat-change <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $revenue_growth >= 0 ? '📈' : '📉'; ?>
                <?php echo ($revenue_growth >= 0 ? '+' : '') . number_format($revenue_growth, 1); ?>% vs previous period
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                🛍️
            </div>
            <h2 class="stat-value"><?php echo number_format($sales_data['total_orders'] ?? 0); ?></h2>
            <p class="stat-label">Total Orders</p>
            <div class="stat-change <?php echo $order_growth >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $order_growth >= 0 ? '📈' : '📉'; ?>
                <?php echo ($order_growth >= 0 ? '+' : '') . number_format($order_growth, 1); ?>% vs previous period
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                📈
            </div>
            <h2 class="stat-value"><?php echo formatCurrency($sales_data['avg_order_value'] ?? 0); ?></h2>
            <p class="stat-label">Avg Order Value</p>
            <div class="stat-change positive">
                <span>📊</span> Performance metric
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                👥
            </div>
            <h2 class="stat-value"><?php echo number_format($customer_analytics['unique_customers'] ?? 0); ?></h2>
            <p class="stat-label">Unique Customers</p>
            <div class="stat-change positive">
                <span>👤</span> Active buyers
            </div>
        </div>
    </div>

    <!-- Report Sections -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6); margin-bottom: var(--spacing-6);">
        <!-- Sales Chart Placeholder -->
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>📉</span> Sales Trends
                </h3>
            </div>
            <div class="card-body-professional">
                <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--gray-100) 0%, #f1f5f9 100%); border-radius: 8px; border: 2px dashed var(--gray-300);">
                    <div style="text-align: center; color: var(--gray-600);">
                        <div style="font-size: 3rem; margin-bottom: var(--spacing-3);">📊</div>
                        <h4 style="margin: 0 0 var(--spacing-2) 0; color: var(--gray-700);">Sales Chart</h4>
                        <p style="margin: 0;">Interactive visualization coming soon</p>
                        <div style="margin-top: var(--spacing-3); text-align: left; background: white; padding: var(--spacing-3); border-radius: 8px; box-shadow: var(--box-shadow);">
                            <strong style="color: var(--primary);">Sample Data:</strong>
                            <?php foreach ($chart_data as $data): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-1); font-size: var(--font-size-sm);">
                                    <span><?php echo date('M j', strtotime($data['date'])); ?>:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($data['revenue']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>🏆</span> Top Selling Products
                </h3>
            </div>
            <div class="card-body-professional">
                <?php if ($top_products && $top_products->num_rows > 0): ?>
                    <?php while($product = $top_products->fetch_assoc()): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-3); border-bottom: 1px solid var(--gray-200);">
                            <div>
                                <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    <?php echo number_format($product['total_sold']); ?> sold
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: var(--success);"><?php echo formatCurrency($product['revenue']); ?></div>
                                <div style="font-size: var(--font-size-sm); color: var(--gray-500);">
                                    <?php echo formatCurrency($product['avg_price']); ?> avg
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: var(--spacing-6); color: var(--gray-500);">
                        <div style="font-size: 3rem; margin-bottom: var(--spacing-3); opacity: 0.5;">📚</div>
                        <p style="margin: 0;">No sales data available for this period</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Additional Insights -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>🔍</span> Detailed Insights
            </h3>
        </div>
        <div class="card-body-professional">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-6);">
                <!-- Category Performance -->
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>📂</span> Category Performance
                    </h4>
                    <?php if ($category_data && $category_data->num_rows > 0): ?>
                        <?php while($category = $category_data->fetch_assoc()): ?>
                            <div style="margin-bottom: var(--spacing-4);">
                                <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-2);">
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($category['revenue']); ?></span>
                                </div>
                                <div style="font-size: var(--font-size-sm); color: var(--gray-600);">
                                    <?php echo number_format($category['total_sold']); ?> items sold from <?php echo $category['products_count']; ?> products
                                </div>
                                <div style="margin-top: var(--spacing-2); height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo min(($category['revenue'] / max($sales_data['total_revenue'], 1)) * 100, 100); ?>%; background: linear-gradient(90deg, var(--primary), var(--secondary)); border-radius: 4px;"></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="color: var(--gray-500); font-style: italic;">No category data available</div>
                    <?php endif; ?>
                </div>
                
                <!-- Customer Analytics -->
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>👥</span> Customer Insights
                    </h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-4);">
                        <div class="stat-card-professional" style="padding: var(--spacing-4); margin-bottom: 0;">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1rem;">👤</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo number_format($customer_analytics['unique_customers'] ?? 0); ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">Unique Customers</p>
                        </div>
                        
                        <div class="stat-card-professional" style="padding: var(--spacing-4); margin-bottom: 0;">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">🔒</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo number_format($customer_analytics['registered_customers'] ?? 0); ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">Registered</p>
                        </div>
                        
                        <div class="stat-card-professional" style="padding: var(--spacing-4); margin-bottom: 0; grid-column: span 2;">
                            <div class="stat-icon" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">🛒</div>
                            <h3 class="stat-value" style="font-size: 1.5rem; margin: var(--spacing-2) 0 0 0;"><?php echo number_format($customer_analytics['avg_orders_per_customer'] ?? 0, 1); ?></h3>
                            <p class="stat-label" style="font-size: var(--font-size-sm); margin: var(--spacing-1) 0 0 0;">Avg Orders/Customer</p>
                        </div>
                    </div>
                </div>
                
                <!-- Order Status Distribution -->
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; color: var(--gray-800); display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>📊</span> Order Status
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-3);">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-2); background: var(--gray-100); border-radius: 8px;">
                            <span style="display: flex; align-items: center; gap: var(--spacing-2);">
                                <span style="color: var(--success);">✅</span> Completed
                            </span>
                            <span style="font-weight: 600;"><?php echo number_format($sales_data['completed_orders'] ?? 0); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-2); background: var(--gray-100); border-radius: 8px;">
                            <span style="display: flex; align-items: center; gap: var(--spacing-2);">
                                <span style="color: var(--warning);">🕰️</span> Pending
                            </span>
                            <span style="font-weight: 600;"><?php echo number_format($sales_data['pending_orders'] ?? 0); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-2); background: var(--gray-100); border-radius: 8px;">
                            <span style="display: flex; align-items: center; gap: var(--spacing-2);">
                                <span style="color: var(--danger);">❌</span> Cancelled
                            </span>
                            <span style="font-weight: 600;"><?php echo number_format($sales_data['cancelled_orders'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>⭐</span> Top Customers
            </h3>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <table class="table-professional">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Avg Order</th>
                        <th>Last Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_customers && $top_customers->num_rows > 0): ?>
                        <?php while($customer = $top_customers->fetch_assoc()): ?>
                            <tr class="fade-in">
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--spacing-3);">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--font-size-sm);">
                                            <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($customer['customer_email']); ?></td>
                                <td><?php echo number_format($customer['total_orders']); ?></td>
                                <td style="font-weight: 700; color: var(--success);"><?php echo formatCurrency($customer['total_spent']); ?></td>
                                <td><?php echo formatCurrency($customer['avg_order_value']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($customer['last_order'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: var(--spacing-8); color: var(--gray-500);">
                                No customer data available for this period
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Professional JavaScript -->
<script>
function exportReport() {
    alert('📊 Report export functionality will be implemented in the next update!');
}

function printReport() {
    window.print();
}

// Initialize professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add table animations
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>