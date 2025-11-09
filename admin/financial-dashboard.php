<?php
/**
 * admin/financial-dashboard.php - Financial Dashboard and Reports
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/FinancialReports.php';

$financial = new FinancialReports($conn);

// Generate today's report
$today = date('Y-m-d');
$financial->generateDailyReport($today);

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get data
$financialSummary = $financial->getFinancialSummary(30);
$reportRange = $financial->getReportRange($startDate, $endDate);
$revenueByCategory = $financial->getRevenueByCategory($startDate, $endDate);
$profitByProduct = $financial->getProfitByProduct($startDate, $endDate, 10);
$monthlyComparison = $financial->getMonthlyComparison(6);
$payouts = $financial->getPayouts();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #007bff; }
        .metric-value { font-size: 28px; font-weight: bold; }
        .metric-label { color: #666; font-size: 13px; margin-top: 5px; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        .positive { color: #28a745; }
        .negative { color: #dc3545; }
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
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="sales-dashboard.php"><i class="bi bi-graph-up"></i> Sales Performance</a>
                <a href="inventory-management.php"><i class="bi bi-box"></i> Inventory</a>
                <a href="orders-management.php"><i class="bi bi-truck"></i> Orders</a>
                <a href="products.php"><i class="bi bi-bag"></i> Products</a>
                <a href="advertising-campaigns.php"><i class="bi bi-megaphone"></i> Advertising</a>
                <a href="financial-dashboard.php" class="active"><i class="bi bi-cash-flow"></i> Financial</a>
                <a href="performance-metrics.php"><i class="bi bi-trophy"></i> Performance</a>
                <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-cash-flow"></i> Financial Dashboard</h2>
                    <p class="mb-0">Revenue, costs, profit analysis and financial reports</p>
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
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Financial Summary Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #28a745;">
                            <div class="metric-value positive">$<?php echo number_format($financialSummary['total_revenue'] ?? 0, 2); ?></div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #dc3545;">
                            <div class="metric-value negative">$<?php echo number_format($financialSummary['total_expenses'] ?? 0, 2); ?></div>
                            <div class="metric-label">Total Expenses</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #007bff;">
                            <div class="metric-value positive">$<?php echo number_format($financialSummary['total_net_profit'] ?? 0, 2); ?></div>
                            <div class="metric-label">Net Profit</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #ffc107;">
                            <div class="metric-value"><?php echo round($financialSummary['avg_profit_margin'] ?? 0, 2); ?>%</div>
                            <div class="metric-label">Avg Profit Margin</div>
                        </div>
                    </div>
                </div>

                <!-- Expense Breakdown -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-pie-chart"></i> Expense Breakdown (30 Days)</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div style="font-size: 12px; margin-bottom: 10px;">
                                        <div><strong>COGS:</strong> $<?php echo number_format($financialSummary['total_cogs'] ?? 0, 2); ?></div>
                                        <div><strong>Referral Fees:</strong> $<?php echo number_format($financialSummary['total_referral_fees'] ?? 0, 2); ?></div>
                                        <div><strong>Fulfillment Fees:</strong> $<?php echo number_format($financialSummary['total_fulfillment_fees'] ?? 0, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="font-size: 12px;">
                                        <div><strong>Advertising:</strong> $<?php echo number_format($financialSummary['total_advertising'] ?? 0, 2); ?></div>
                                        <div><strong>Gross Profit:</strong> $<?php echo number_format($financialSummary['total_gross_profit'] ?? 0, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-graph-up"></i> Monthly Comparison</h5>
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue by Category -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-tag"></i> Revenue by Category</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Revenue</th>
                                    <th>Items Sold</th>
                                    <th>Avg Price</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalRevenue = array_sum(array_column($revenueByCategory, 'revenue'));
                                foreach ($revenueByCategory as $category):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>$<?php echo number_format($category['revenue'], 2); ?></td>
                                    <td><?php echo $category['items_sold']; ?></td>
                                    <td>$<?php echo number_format($category['avg_price'], 2); ?></td>
                                    <td><?php echo round(($category['revenue'] / $totalRevenue) * 100, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Profitable Products -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-star"></i> Top 10 Profitable Products</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>COGS</th>
                                    <th>Profit</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($profitByProduct as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['units_sold']; ?></td>
                                    <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($product['estimated_cogs'], 2); ?></td>
                                    <td class="positive"><strong>$<?php echo number_format($product['net_profit'], 2); ?></strong></td>
                                    <td><?php echo round(($product['net_profit'] / $product['total_revenue']) * 100, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Daily Financial Reports -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-calendar"></i> Daily Financial Reports</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Revenue</th>
                                    <th>COGS</th>
                                    <th>Fees</th>
                                    <th>Gross Profit</th>
                                    <th>Net Profit</th>
                                    <th>Margin %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportRange as $report): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                                    <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($report['total_cogs'], 2); ?></td>
                                    <td>$<?php echo number_format($report['referral_fees'] + $report['fulfillment_fees'], 2); ?></td>
                                    <td>$<?php echo number_format($report['gross_profit'], 2); ?></td>
                                    <td class="<?php echo $report['net_profit'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <strong>$<?php echo number_format($report['net_profit'], 2); ?></strong>
                                    </td>
                                    <td><?php echo round($report['profit_margin'], 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payouts -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-bank"></i> Recent Payouts</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Payout ID</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Fee</th>
                                    <th>Net Payout</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($payouts, 0, 10) as $payout): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payout['payout_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d', strtotime($payout['payout_period_start'])) . ' - ' . date('M d', strtotime($payout['payout_period_end'])); ?></td>
                                    <td>$<?php echo number_format($payout['payout_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($payout['transaction_fee'], 2); ?></td>
                                    <td>$<?php echo number_format($payout['net_payout'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $payout['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($payout['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Export -->
                <div class="dashboard-card">
                    <a href="export.php?type=financial&format=csv&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-download"></i> Export Financial Report (CSV)
                    </a>
                    <a href="export.php?type=financial&format=pdf&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-pdf"></i> Export Financial Report (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Comparison Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode(array_reverse($monthlyComparison)); ?>;

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(m => m.month),
                datasets: [
                    {
                        label: 'Revenue',
                        data: monthlyData.map(m => m.revenue),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Profit',
                        data: monthlyData.map(m => m.profit),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } }
            }
        });
    </script>
</body>
</html>
