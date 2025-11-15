<?php
/**
 * admin/sales-dashboard.php - Enhanced Sales Performance Dashboard
 */

session_start();

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/AdminDashboard.php';

$dashboard = new AdminDashboard($conn);

// Get date range from request
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'daily'; // daily, weekly, monthly

// Get metrics
$salesMetrics = $dashboard->getSalesMetrics($startDate, $endDate);
$dailySales = $dashboard->getDailySalesData(30);
$revenueByCategory = $dashboard->getRevenueByCategory($startDate, $endDate);
$topProducts = $dashboard->getTopProducts(10);
$customerInsights = $dashboard->getCustomerInsights($startDate, $endDate);
$orderStatus = $dashboard->getOrderStatusBreakdown($startDate, $endDate);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Performance Dashboard - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 20px; border-left: 4px solid #007bff; }
        .metric-value { font-size: 32px; font-weight: bold; color: #007bff; }
        .metric-label { color: #666; font-size: 14px; margin-top: 5px; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
        .header { background-color: #34495e; color: white; padding: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="text-white"><i class="bi bi-bar-chart"></i> Bookory Admin</h5>
                </div>
                <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="sales-dashboard.php"><i class="bi bi-graph-up"></i> Sales Performance</a>
                <a href="inventory-management.php"><i class="bi bi-box"></i> Inventory</a>
                <a href="orders-management.php"><i class="bi bi-truck"></i> Orders</a>
                <a href="products.php"><i class="bi bi-bag"></i> Products</a>
                <a href="advertising-campaigns.php"><i class="bi bi-megaphone"></i> Advertising</a>
                <a href="financial-dashboard.php"><i class="bi bi-cash-flow"></i> Financial</a>
                <a href="performance-metrics.php"><i class="bi bi-trophy"></i> Performance</a>
                <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="header mb-4 rounded">
                    <h2><i class="bi bi-graph-up"></i> Sales Performance Dashboard</h2>
                    <p class="mb-0">Real-time sales analytics and insights</p>
                </div>

                <!-- Date Range Filter -->
                <div class="dashboard-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            <select name="period" class="form-select">
                                <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Key Metrics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="dashboard-card metric-box">
                            <div class="metric-value text-success">
                                $<?php echo number_format($salesMetrics['total_revenue'] ?? 0, 2); ?>
                            </div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card metric-box">
                            <div class="metric-value text-info">
                                <?php echo $salesMetrics['total_orders'] ?? 0; ?>
                            </div>
                            <div class="metric-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card metric-box">
                            <div class="metric-value text-warning">
                                <?php echo $salesMetrics['unique_customers'] ?? 0; ?>
                            </div>
                            <div class="metric-label">Unique Customers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card metric-box">
                            <div class="metric-value text-primary">
                                $<?php echo number_format(($salesMetrics['total_revenue'] ?? 0) / max(1, $salesMetrics['total_orders'] ?? 1), 2); ?>
                            </div>
                            <div class="metric-label">Avg Order Value</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-graph-up"></i> Daily Revenue Trend</h5>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-pie-chart"></i> Revenue by Category</h5>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products & Order Status -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-star"></i> Top 10 Products</h5>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo $product['units_sold']; ?></td>
                                        <td>$<?php echo number_format($product['total_revenue'] ?? 0, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-check-circle"></i> Order Status Breakdown</h5>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Buttons -->
                <div class="dashboard-card mt-4">
                    <h5>Export Reports</h5>
                    <a href="export.php?type=sales&format=csv&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-download"></i> Export Sales (CSV)
                    </a>
                    <a href="export.php?type=sales&format=pdf&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-pdf"></i> Export Sales (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode(array_reverse($dailySales)); ?>;

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => d.sale_date),
                datasets: [{
                    label: 'Daily Revenue',
                    data: revenueData.map(d => d.revenue),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($revenueByCategory); ?>;

        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.category_name),
                datasets: [{
                    data: categoryData.map(c => c.total_revenue),
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Order Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = <?php echo json_encode($orderStatus); ?>;

        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: statusData.map(s => s.order_status),
                datasets: [{
                    label: 'Order Count',
                    data: statusData.map(s => s.count),
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>
