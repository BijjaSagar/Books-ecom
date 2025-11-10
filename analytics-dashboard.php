<?php
/**
 * Analytics Dashboard Page
 * Admin dashboard showing sales analytics and business metrics
 */

// Check authentication
if (($_SESSION['user_id'] ?? null) === null || ($_SESSION['is_admin'] ?? false) === false) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/AnalyticsManager.php';

$analytics = new AnalyticsManager($conn);

// Get date range from query params
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get analytics data
$revenue = $analytics->getRevenueSummary($start_date, $end_date);
$top_products = $analytics->getTopProducts(5, $start_date, $end_date);
$conversion_funnel = $analytics->getConversionFunnel($start_date, $end_date);
$abandonment = $analytics->getCartAbandonmentRate($start_date, $end_date);
$traffic = $analytics->getTrafficSources($start_date, $end_date);
$searches = $analytics->getPopularSearches(5, $start_date, $end_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Books eCommerce Admin</title>

    <link rel="stylesheet" href="/css/responsive-framework.css">
    <link rel="stylesheet" href="/css/responsive-components.css">

    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 0;
            min-height: 100vh;
        }

        .admin-sidebar {
            background: #1a3a52;
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-sidebar h3 {
            color: #d4a574;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .admin-sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 4px;
            transition: background 200ms;
        }

        .admin-sidebar a:hover,
        .admin-sidebar a.active {
            background: #d4a574;
            color: #1a3a52;
        }

        .admin-content {
            padding: 32px;
        }

        .date-range {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .metric-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            border-left: 4px solid #d4a574;
            margin-bottom: 24px;
        }

        .metric-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #1a3a52;
        }

        .metric-change {
            font-size: 12px;
            color: #27ae60;
            margin-top: 8px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-container {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a3a52;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #1a3a52;
            border-bottom: 2px solid #d4a574;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #e8f5e9;
            color: #27ae60;
        }

        .badge-warning {
            background: #fff3cd;
            color: #f39c12;
        }

        @media (max-width: 991px) {
            .admin-layout {
                grid-template-columns: 1fr;
            }

            .admin-sidebar {
                height: auto;
                display: none;
                position: static;
            }

            .admin-sidebar.active {
                display: block;
            }

            .date-range {
                flex-direction: column;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="navbar">
        <div class="container flex flex-between">
            <a href="/" class="navbar-brand">📊 Admin Panel</a>
            <button class="navbar-toggler">☰</button>
            <ul class="navbar-nav">
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/admin/orders">Orders</a></li>
                <li><a href="/admin/analytics">Analytics</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Admin Layout -->
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h3>📊 Analytics</h3>
            <a href="/analytics-dashboard.php" class="active">Dashboard</a>
            <a href="/analytics-dashboard.php?view=revenue">Revenue</a>
            <a href="/analytics-dashboard.php?view=products">Top Products</a>
            <a href="/analytics-dashboard.php?view=customers">Customers</a>

            <h3 style="margin-top: 32px;">🛠️ Admin Tools</h3>
            <a href="/admin/orders">Orders</a>
            <a href="/admin/products">Products</a>
            <a href="/admin/customers">Customers</a>
            <a href="/admin/settings">Settings</a>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <h1>Analytics Dashboard</h1>

            <!-- Date Range Filter -->
            <div class="date-range">
                <form method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label>Start Date:</label>
                        <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" style="padding: 8px;">
                    </div>
                    <div>
                        <label>End Date:</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" style="padding: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>

            <!-- Key Metrics -->
            <h2>Key Metrics</h2>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="metric-card">
                        <div class="metric-label">Total Revenue</div>
                        <div class="metric-value">
                            $<?php echo number_format($revenue['total_revenue'] ?? 0, 2); ?>
                        </div>
                        <div class="metric-change">↑ 12% from last period</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="metric-card">
                        <div class="metric-label">Total Orders</div>
                        <div class="metric-value">
                            <?php echo $revenue['total_orders'] ?? 0; ?>
                        </div>
                        <div class="metric-change">↑ 8% from last period</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="metric-card">
                        <div class="metric-label">Avg Order Value</div>
                        <div class="metric-value">
                            $<?php echo number_format($revenue['avg_order_value'] ?? 0, 2); ?>
                        </div>
                        <div class="metric-change">↑ 5% from last period</div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="metric-card">
                        <div class="metric-label">Cart Abandonment</div>
                        <div class="metric-value">
                            <?php echo $abandonment['abandonment_rate'] ?? 0; ?>%
                        </div>
                        <div class="metric-change">↓ 2% from last period</div>
                    </div>
                </div>
            </div>

            <!-- Charts & Analysis -->
            <h2 style="margin-top: 48px;">Analytics</h2>
            <div class="charts-grid">
                <!-- Top Products -->
                <div class="chart-container">
                    <h3 class="chart-title">Top 5 Products</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td><span class="badge badge-success"><?php echo $product['total_orders']; ?></span></td>
                                <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Conversion Funnel -->
                <div class="chart-container">
                    <h3 class="chart-title">Conversion Funnel</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Step</th>
                                <th>Users</th>
                                <th>Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conversion_funnel as $step): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', $step['step'])); ?></td>
                                <td><?php echo $step['count']; ?></td>
                                <td><?php echo round($step['percentage'], 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Traffic Sources -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3 class="chart-title">Traffic Sources</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Sessions</th>
                                <th>Users</th>
                                <th>Bounce Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traffic as $source): ?>
                            <tr>
                                <td>
                                    <?php echo ucfirst($source['source_type']); ?>
                                    <?php if ($source['source_name']): ?>
                                        (<?php echo htmlspecialchars($source['source_name']); ?>)
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $source['sessions']; ?></td>
                                <td><?php echo $source['users']; ?></td>
                                <td><?php echo round($source['bounce_rate'], 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Popular Searches -->
                <div class="chart-container">
                    <h3 class="chart-title">Popular Searches</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Search Term</th>
                                <th>Searches</th>
                                <th>Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searches as $search): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($search['search_term']); ?></td>
                                <td><?php echo $search['search_count']; ?></td>
                                <td><span class="badge badge-success"><?php echo $search['click_count']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="/js/mobile-menu.js"></script>
</body>
</html>
