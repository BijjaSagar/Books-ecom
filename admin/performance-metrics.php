<?php
/**
 * admin/performance-metrics.php - Performance Metrics and Account Health
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/AdminDashboard.php';

$dashboard = new AdminDashboard($conn);

// Calculate and get health score
$healthScore = $dashboard->calculateHealthScore();
$accountHealth = $dashboard->getAccountHealth();

// Get metrics
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');
$salesMetrics = $dashboard->getSalesMetrics($startDate, $endDate);
$customerInsights = $dashboard->getCustomerInsights($startDate, $endDate);
$topProducts = $dashboard->getTopProducts(10, 30);

// Get reviews/feedback if available
$stmt = $conn->prepare("
    SELECT
        AVG(rating) as avg_rating,
        COUNT(id) as review_count,
        SUM(CASE WHEN feedback_type = 'positive' THEN 1 ELSE 0 END) as positive_reviews,
        SUM(CASE WHEN feedback_type = 'negative' THEN 1 ELSE 0 END) as negative_reviews
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$reviews = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Metrics - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border-top: 4px solid #007bff; }
        .metric-value { font-size: 32px; font-weight: bold; color: #007bff; }
        .health-score-circle { width: 150px; height: 150px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 36px; font-weight: bold; color: white; }
        .health-excellent { background: linear-gradient(135deg, #28a745, #20c997); }
        .health-good { background: linear-gradient(135deg, #ffc107, #ff9800); }
        .health-poor { background: linear-gradient(135deg, #dc3545, #c82333); }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
        .rating-stars { color: #ffc107; font-size: 20px; }
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
                <a href="financial-dashboard.php"><i class="bi bi-cash-flow"></i> Financial</a>
                <a href="performance-metrics.php" class="active"><i class="bi bi-trophy"></i> Performance</a>
                <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-trophy"></i> Performance Metrics</h2>
                    <p class="mb-0">Account health, customer feedback, and performance analytics</p>
                </div>

                <!-- Account Health Score -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="dashboard-card text-center">
                            <h5 class="mb-4">Account Health Score</h5>
                            <?php
                            $score = $accountHealth['health_score'] ?? 100;
                            $healthClass = $score >= 90 ? 'health-excellent' : ($score >= 70 ? 'health-good' : 'health-poor');
                            ?>
                            <div class="health-score-circle <?php echo $healthClass; ?>">
                                <?php echo round($score); ?>%
                            </div>
                            <p class="mt-3 text-muted">
                                <?php
                                if ($score >= 90) echo "Excellent - No issues";
                                elseif ($score >= 70) echo "Good - Some attention needed";
                                else echo "Poor - Immediate action required";
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>Health Metrics</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Seller Rating</span>
                                    <span class="rating-stars">
                                        <?php for ($i = 0; $i < round($accountHealth['seller_rating'] ?? 0); $i++): ?>
                                        ★
                                        <?php endfor; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Negative Feedback</span>
                                    <span class="badge bg-danger"><?php echo $accountHealth['negative_feedback_count'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Policy Violations</span>
                                    <span class="badge bg-warning"><?php echo $accountHealth['policy_violations'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Return Defect Rate</span>
                                    <span><?php echo round($accountHealth['return_defect_rate'] ?? 0, 2); ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-box" style="border-top-color: #28a745;">
                            <div class="metric-value" style="color: #28a745;">
                                <?php echo $salesMetrics['total_orders'] ?? 0; ?>
                            </div>
                            <small>Total Orders (30d)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-top-color: #17a2b8;">
                            <div class="metric-value" style="color: #17a2b8;">
                                $<?php echo number_format(($salesMetrics['total_revenue'] ?? 0) / max(1, $salesMetrics['total_orders'] ?? 1), 2); ?>
                            </div>
                            <small>Avg Order Value</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-top-color: #ffc107;">
                            <div class="metric-value" style="color: #ffc107;">
                                <?php echo round(($salesMetrics['completed_orders'] ?? 0) / max(1, $salesMetrics['total_orders'] ?? 1) * 100, 1); ?>%
                            </div>
                            <small>Fulfillment Rate</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-top-color: #dc3545;">
                            <div class="metric-value" style="color: #dc3545;">
                                <?php echo $customerInsights['new_customers'] ?? 0; ?>
                            </div>
                            <small>New Customers</small>
                        </div>
                    </div>
                </div>

                <!-- Customer Feedback & Reviews -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-chat-left-quote"></i> Customer Feedback (30 Days)</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <h3 class="text-info"><?php echo $reviews['review_count'] ?? 0; ?></h3>
                                <small>Total Reviews</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <h3 class="rating-stars">
                                    <?php for ($i = 0; $i < round($reviews['avg_rating'] ?? 0); $i++): ?>★<?php endfor; ?>
                                </h3>
                                <small>Avg Rating</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border-end">
                                <h3 class="text-success"><?php echo $reviews['positive_reviews'] ?? 0; ?></h3>
                                <small>Positive</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <h3 class="text-danger"><?php echo $reviews['negative_reviews'] ?? 0; ?></h3>
                                <small>Negative</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Insights -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-people"></i> Customer Insights (30 Days)</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Total Customers</div>
                                <div style="font-size: 24px; font-weight: bold;"><?php echo $customerInsights['total_customers'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">New Customers</div>
                                <div style="font-size: 24px; font-weight: bold;"><?php echo $customerInsights['new_customers'] ?? 0; ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Repeat Rate</div>
                                <div style="font-size: 24px; font-weight: bold;">
                                    <?php
                                    $repeatRate = ($customerInsights['total_customers'] - $customerInsights['new_customers']) / max(1, $customerInsights['total_customers']) * 100;
                                    echo round($repeatRate, 1); ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Selling Products -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-graph-up"></i> Top Selling Products (30 Days)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Avg Price</th>
                                    <th>Conversion Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['units_sold']; ?></td>
                                    <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($product['avg_price'], 2); ?></td>
                                    <td>3.2%</td> <!-- Example -->
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Performance Recommendations -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-lightbulb"></i> Recommendations</h5>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> Based on your account performance:
                    </div>
                    <ul class="list-unstyled">
                        <?php if ($accountHealth['health_score'] < 90): ?>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            Improve your health score by addressing negative feedback and returns
                        </li>
                        <?php endif; ?>

                        <?php if (($reviews['negative_reviews'] ?? 0) > ($reviews['positive_reviews'] ?? 0) * 0.1): ?>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-circle text-warning"></i>
                            Address customer complaints to improve your rating
                        </li>
                        <?php endif; ?>

                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            Focus on your top 3 categories to maximize revenue
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i>
                            Increase marketing spend on high-performing products
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
