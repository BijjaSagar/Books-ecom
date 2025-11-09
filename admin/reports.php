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
    p.name,
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
    COALESCE(o.first_name, 'Guest') as customer_name,
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

// Inject professional CSS
injectProfessionalCSS();
renderProfessionalJavaScript();
?>

<div class="admin-container">
    <!-- Professional Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📈 Reports & Analytics</h1>
                <p class="page-subtitle">Business insights and performance metrics</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn-professional btn-outline-professional" onclick="exportReport()">
                    <span>📥</span> Export Report
                </button>
                <button class="btn-professional btn-primary-professional" onclick="printReport()">
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
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales</option>
                    <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Products</option>
                    <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>Customers</option>
                </select>
            </div>
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>🔍</span> Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card-professional">
            <div class="stat-icon">💰</div>
            <h3 class="stat-value"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($sales_data['total_revenue'] ?? 0, 2); ?></h3>
            <p class="stat-label">Total Revenue</p>
            <?php 
            $revenue_change = ($sales_data['total_revenue'] ?? 0) - ($prev_sales_data['total_revenue'] ?? 0);
            $revenue_percent = ($prev_sales_data['total_revenue'] ?? 0) > 0 ? 
                round(($revenue_change / $prev_sales_data['total_revenue']) * 100, 1) : 0;
            ?>
            <div class="stat-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                <span><?php echo $revenue_change >= 0 ? '📈' : '📉'; ?></span>
                <?php echo abs($revenue_percent); ?>% vs previous period
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                🛒
            </div>
            <h3 class="stat-value"><?php echo number_format($sales_data['total_orders'] ?? 0); ?></h3>
            <p class="stat-label">Total Orders</p>
            <?php 
            $orders_change = ($sales_data['total_orders'] ?? 0) - ($prev_sales_data['total_orders'] ?? 0);
            $orders_percent = ($prev_sales_data['total_orders'] ?? 0) > 0 ? 
                round(($orders_change / $prev_sales_data['total_orders']) * 100, 1) : 0;
            ?>
            <div class="stat-change <?php echo $orders_change >= 0 ? 'positive' : 'negative'; ?>">
                <span><?php echo $orders_change >= 0 ? '📈' : '📉'; ?></span>
                <?php echo abs($orders_percent); ?>% vs previous period
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                💹
            </div>
            <h3 class="stat-value"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($sales_data['avg_order_value'] ?? 0, 2); ?></h3>
            <p class="stat-label">Avg. Order Value</p>
            <?php 
            $aov_change = ($sales_data['avg_order_value'] ?? 0) - ($prev_sales_data['avg_order_value'] ?? 0);
            $aov_percent = ($prev_sales_data['avg_order_value'] ?? 0) > 0 ? 
                round(($aov_change / $prev_sales_data['avg_order_value']) * 100, 1) : 0;
            ?>
            <div class="stat-change <?php echo $aov_change >= 0 ? 'positive' : 'negative'; ?>">
                <span><?php echo $aov_change >= 0 ? '📈' : '📉'; ?></span>
                <?php echo abs($aov_percent); ?>% vs previous period
            </div>
        </div>
        
        <div class="stat-card-professional">
            <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                👥
            </div>
            <h3 class="stat-value"><?php echo number_format($customer_analytics['unique_customers'] ?? 0); ?></h3>
            <p class="stat-label">Unique Customers</p>
            <div class="stat-change">
                <span>📋</span>
                <?php echo number_format($customer_analytics['registered_customers'] ?? 0); ?> registered
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Sales Trend Chart -->
        <div class="col-lg-8 mb-4">
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>📊</span> Sales Trend</h5>
                </div>
                <div class="card-body-professional">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Order Status Pie Chart -->
        <div class="col-lg-4 mb-4">
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>🥧</span> Order Status</h5>
                </div>
                <div class="card-body-professional">
                    <canvas id="statusChart"></canvas>
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <strong><?php echo $sales_data['completed_orders'] ?? 0; ?></strong>
                                <br><small class="text-success">Completed</small>
                            </div>
                            <div class="col-4">
                                <strong><?php echo $sales_data['pending_orders'] ?? 0; ?></strong>
                                <br><small class="text-warning">Pending</small>
                            </div>
                            <div class="col-4">
                                <strong><?php echo $sales_data['cancelled_orders'] ?? 0; ?></strong>
                                <br><small class="text-danger">Cancelled</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>🏆</span> Top Selling Products</h5>
                </div>
                <div class="card-body-professional p-0">
                    <div class="table-responsive">
                        <table class="table-professional">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($top_products && $top_products->num_rows > 0): ?>
                                    <?php while ($product = $top_products->fetch_assoc()): ?>
                                        <tr class="fade-in">
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <br><small class="text-muted"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($product['price'], 2); ?></small>
                                            </td>
                                            <td><span class="status-badge-professional status-info"><?php echo $product['total_sold']; ?></span></td>
                                            <td><strong><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($product['revenue'], 2); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted">No sales data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Customers -->
        <div class="col-lg-6 mb-4">
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>⭐</span> Top Customers</h5>
                </div>
                <div class="card-body-professional p-0">
                    <div class="table-responsive">
                        <table class="table-professional">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($top_customers && $top_customers->num_rows > 0): ?>
                                    <?php while ($customer = $top_customers->fetch_assoc()): ?>
                                        <tr class="fade-in">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="customer-avatar me-2" style="width: 30px; height: 30px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">
                                                        <?php echo strtoupper(substr($customer['customer_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($customer['customer_email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="status-badge-professional status-info"><?php echo $customer['total_orders']; ?></span></td>
                                            <td><strong><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3 text-muted">No customer data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="row">
        <div class="col-12">
            <div class="professional-card">
                <div class="card-header-professional">
                    <h5 class="card-title"><span>🏷️</span> Category Performance</h5>
                </div>
                <div class="card-body-professional p-0">
                    <div class="table-responsive">
                        <table class="table-professional">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Products</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($category_data && $category_data->num_rows > 0): ?>
                                    <?php 
                                    $total_category_revenue = 0;
                                    $categories = [];
                                    while ($category = $category_data->fetch_assoc()) {
                                        $categories[] = $category;
                                        $total_category_revenue += $category['revenue'];
                                    }
                                    ?>
                                    <?php foreach ($categories as $category): ?>
                                        <?php $percentage = $total_category_revenue > 0 ? ($category['revenue'] / $total_category_revenue) * 100 : 0; ?>
                                        <tr class="fade-in">
                                            <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                                            <td><?php echo $category['products_count']; ?> products</td>
                                            <td><span class="status-badge-professional status-info"><?php echo $category['total_sold']; ?></span></td>
                                            <td><strong><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($category['revenue'], 2); ?></strong></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%">
                                                        <?php echo round($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">No category data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Trend Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const chartData = <?php echo json_encode($chart_data); ?>;

const salesLabels = chartData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

const salesRevenue = chartData.map(item => parseFloat(item.revenue) || 0);
const salesOrders = chartData.map(item => parseInt(item.orders) || 0);

new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Revenue',
            data: salesRevenue,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            yAxisID: 'y'
        }, {
            label: 'Orders',
            data: salesOrders,
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            borderWidth: 2,
            fill: false,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                ticks: {
                    callback: function(value) {
                        return '<?php echo $settings['currency_symbol'] ?? '₹'; ?>' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

// Order Status Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const completedOrders = <?php echo $sales_data['completed_orders'] ?? 0; ?>;
const pendingOrders = <?php echo $sales_data['pending_orders'] ?? 0; ?>;
const cancelledOrders = <?php echo $sales_data['cancelled_orders'] ?? 0; ?>;

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Pending', 'Cancelled'],
        datasets: [{
            data: [completedOrders, pendingOrders, cancelledOrders],
            backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

function exportReport() {
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    window.open(`export_report.php?start_date=${startDate}&end_date=${endDate}&format=csv`, '_blank');
}

function printReport() {
    window.print();
}
</script>

<?php include '../includes/admin_footer.php'; ?>