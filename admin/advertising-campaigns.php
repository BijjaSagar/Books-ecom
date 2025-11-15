<?php
/**
 * admin/advertising-campaigns.php - Advertising Campaigns Management
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/AdvertisingManager.php';

$adManager = new AdvertisingManager($conn);

// Handle campaign actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] ?? '' === 'create_campaign') {
        $campaignData = [
            'name' => $_POST['campaign_name'],
            'type' => $_POST['campaign_type'],
            'budget' => $_POST['budget'],
            'daily_budget' => $_POST['daily_budget'],
            'status' => $_POST['status'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date']
        ];
        $adManager->createCampaign($campaignData);
    } elseif ($_POST['action'] ?? '' === 'pause_campaign') {
        $adManager->pauseCampaign($_POST['campaign_id']);
    } elseif ($_POST['action'] ?? '' === 'activate_campaign') {
        $adManager->activateCampaign($_POST['campaign_id']);
    }
    header('Location: advertising-campaigns.php');
    exit;
}

$status = $_GET['status'] ?? null;
$campaigns = $adManager->getCampaigns($status);
$campaignPerformance = $adManager->getCampaignPerformance();
$roiAnalysis = $adManager->getRoiAnalysis();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertising Campaigns - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
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
                <a href="advertising-campaigns.php" class="active"><i class="bi bi-megaphone"></i> Advertising</a>
                <a href="financial-dashboard.php"><i class="bi bi-cash-flow"></i> Financial</a>
                <a href="performance-metrics.php"><i class="bi bi-trophy"></i> Performance</a>
                <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-megaphone"></i> Advertising Campaigns</h2>
                    <p class="mb-0">Manage sponsored products and advertising performance</p>
                </div>

                <!-- Performance Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-box">
                            <h5 class="text-primary"><?php echo $campaignPerformance['total_campaigns'] ?? 0; ?></h5>
                            <small>Total Campaigns</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <h5 class="text-success">$<?php echo number_format($campaignPerformance['total_spend'] ?? 0, 2); ?></h5>
                            <small>Total Spend</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <h5 class="text-info">$<?php echo number_format($campaignPerformance['total_sales'] ?? 0, 2); ?></h5>
                            <small>Total Sales</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <h5 class="text-warning"><?php echo round($campaignPerformance['avg_roas'] ?? 0, 2); ?>%</h5>
                            <small>Avg ROAS</small>
                        </div>
                    </div>
                </div>

                <!-- Create New Campaign -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-plus-circle"></i> Create New Campaign</h5>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create_campaign">
                        <div class="col-md-3">
                            <label class="form-label">Campaign Name</label>
                            <input type="text" name="campaign_name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="campaign_type" class="form-select" required>
                                <option value="Sponsored Products">Sponsored Products</option>
                                <option value="Sponsored Brands">Sponsored Brands</option>
                                <option value="Display">Display</option>
                                <option value="Manual CPC">Manual CPC</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Budget</label>
                            <input type="number" step="0.01" name="budget" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Daily Budget</label>
                            <input type="number" step="0.01" name="daily_budget" class="form-control" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check"></i> Create</button>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </form>
                </div>

                <!-- ROI Analysis -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-graph-up"></i> Campaign ROI Analysis</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign Name</th>
                                    <th>Spend</th>
                                    <th>Sales</th>
                                    <th>ROI %</th>
                                    <th>ACOS</th>
                                    <th>ROAS</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roiAnalysis as $campaign): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                    <td>$<?php echo number_format($campaign['spend'], 2); ?></td>
                                    <td>$<?php echo number_format($campaign['sales'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $campaign['roi_percentage'] >= 100 ? 'success' : 'warning'; ?>"><?php echo round($campaign['roi_percentage'], 2); ?>%</span></td>
                                    <td><?php echo round($campaign['acos'], 2); ?>%</td>
                                    <td><?php echo round($campaign['roas'], 2); ?></td>
                                    <td><span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($campaign['status']); ?></span></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                            <?php if ($campaign['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="pause_campaign">
                                            <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-pause"></i> Pause</button>
                                            <?php else: ?>
                                            <input type="hidden" name="action" value="activate_campaign">
                                            <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-play"></i> Activate</button>
                                            <?php endif; ?>
                                        </form>
                                        <a href="campaign-details.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- All Campaigns -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-list"></i> All Campaigns</h5>

                    <!-- Filter -->
                    <div class="mb-3">
                        <a href="advertising-campaigns.php" class="btn btn-sm btn-outline-primary <?php echo empty($status) ? 'active' : ''; ?>">All</a>
                        <a href="?status=active" class="btn btn-sm btn-outline-success <?php echo $status === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="?status=paused" class="btn btn-sm btn-outline-warning <?php echo $status === 'paused' ? 'active' : ''; ?>">Paused</a>
                        <a href="?status=archived" class="btn btn-sm btn-outline-secondary <?php echo $status === 'archived' ? 'active' : ''; ?>">Archived</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Type</th>
                                    <th>Spend</th>
                                    <th>Impressions</th>
                                    <th>Clicks</th>
                                    <th>Conversions</th>
                                    <th>CPC</th>
                                    <th>Status</th>
                                    <th>Period</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                                    <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                                    <td>$<?php echo number_format($campaign['spend'], 2); ?></td>
                                    <td><?php echo $campaign['impressions']; ?></td>
                                    <td><?php echo $campaign['clicks']; ?></td>
                                    <td><?php echo $campaign['conversions']; ?></td>
                                    <td>$<?php echo number_format($campaign['cpc'], 3); ?></td>
                                    <td><span class="badge bg-<?php echo $campaign['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($campaign['status']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($campaign['start_date'])) . ' - ' . date('M d, Y', strtotime($campaign['end_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Export -->
                <div class="dashboard-card">
                    <a href="export.php?type=campaigns&format=csv" class="btn btn-success btn-sm">
                        <i class="bi bi-download"></i> Export Campaigns (CSV)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
