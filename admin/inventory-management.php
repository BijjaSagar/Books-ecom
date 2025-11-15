<?php
/**
 * admin/inventory-management.php - Inventory Management Dashboard
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/InventoryManager.php';

$inventory = new InventoryManager($conn);

// Get filter
$filter = $_GET['filter'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle alert resolution
if ($_POST['action'] ?? '' === 'resolve_alert' && isset($_POST['alert_id'])) {
    $inventory->resolveAlert($_POST['alert_id']);
    header('Location: inventory-management.php');
    exit;
}

// Get data
$inventoryList = $inventory->getInventoryList($limit, $offset, $filter);
$inventoryCount = $inventory->getInventoryCount($filter);
$lowStockAlerts = $inventory->getLowStockAlerts(20);
$inventorySummary = $inventory->getInventorySummary();
$reorderRecommendations = $inventory->getReorderRecommendations();

$totalPages = ceil($inventoryCount / $limit);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .summary-box { padding: 15px; border-left: 4px solid #007bff; margin-bottom: 15px; }
        .alert-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .low-stock { background-color: #fff3cd; color: #856404; }
        .out-of-stock { background-color: #f8d7da; color: #721c24; }
        .in-stock { background-color: #d4edda; color: #155724; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
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
                <a href="inventory-management.php" class="active"><i class="bi bi-box"></i> Inventory</a>
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
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-box"></i> Inventory Management</h2>
                    <p class="mb-0">Monitor stock levels, manage alerts, and optimize inventory</p>
                </div>

                <!-- Inventory Summary -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3 class="text-info"><?php echo $inventorySummary['total_products'] ?? 0; ?></h3>
                            <small>Total Products</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3 class="text-success"><?php echo $inventorySummary['total_units'] ?? 0; ?></h3>
                            <small>Total Units</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3 class="text-warning"><?php echo $inventorySummary['low_stock'] ?? 0; ?></h3>
                            <small>Low Stock</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3 class="text-danger"><?php echo $inventorySummary['out_of_stock'] ?? 0; ?></h3>
                            <small>Out of Stock</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3 class="text-info"><?php echo $inventorySummary['overstock'] ?? 0; ?></h3>
                            <small>Overstock</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="dashboard-card text-center">
                            <h3><?php echo round($inventorySummary['avg_stock'] ?? 0); ?></h3>
                            <small>Avg per Product</small>
                        </div>
                    </div>
                </div>

                <!-- Active Alerts -->
                <?php if (!empty($lowStockAlerts)): ?>
                <div class="dashboard-card">
                    <h5><i class="bi bi-exclamation-triangle"></i> Active Stock Alerts (<?php echo count($lowStockAlerts); ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Alert Type</th>
                                    <th>Category</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockAlerts as $alert): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($alert['name']); ?></td>
                                    <td><?php echo htmlspecialchars($alert['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo $alert['current_stock']; ?></td>
                                    <td>
                                        <span class="alert-badge <?php echo 'low-stock'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $alert['alert_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($alert['category'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($alert['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="resolve_alert">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark as resolved?');">
                                                <i class="bi bi-check"></i> Resolve
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Reorder Recommendations -->
                <?php if (!empty($reorderRecommendations)): ?>
                <div class="dashboard-card">
                    <h5><i class="bi bi-arrow-repeat"></i> Reorder Recommendations</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Current Stock</th>
                                    <th>Monthly Sales</th>
                                    <th>Recommended Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reorderRecommendations as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['stock_quantity']; ?></td>
                                    <td><?php echo $item['avg_monthly_sales']; ?></td>
                                    <td><?php echo ceil($item['avg_monthly_sales'] * 1.5); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Inventory List with Filters -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-list"></i> All Products</h5>

                    <!-- Filter Buttons -->
                    <div class="mb-3">
                        <a href="inventory-management.php" class="btn btn-sm btn-outline-primary <?php echo empty($filter) ? 'active' : ''; ?>">
                            All
                        </a>
                        <a href="?filter=low_stock" class="btn btn-sm btn-outline-warning <?php echo $filter === 'low_stock' ? 'active' : ''; ?>">
                            <i class="bi bi-exclamation-circle"></i> Low Stock
                        </a>
                        <a href="?filter=out_of_stock" class="btn btn-sm btn-outline-danger <?php echo $filter === 'out_of_stock' ? 'active' : ''; ?>">
                            <i class="bi bi-x-circle"></i> Out of Stock
                        </a>
                        <a href="?filter=overstock" class="btn btn-sm btn-outline-info <?php echo $filter === 'overstock' ? 'active' : ''; ?>">
                            <i class="bi bi-check-circle"></i> Overstock
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventoryList as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($product['stock_status']) {
                                            'out_of_stock' => 'danger',
                                            'low_stock' => 'warning',
                                            'overstock' => 'info',
                                            default => 'success'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $product['stock_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>

                <!-- Export -->
                <div class="dashboard-card">
                    <a href="export.php?type=inventory&format=csv&filter=<?php echo urlencode($filter); ?>" class="btn btn-success btn-sm">
                        <i class="bi bi-download"></i> Export Inventory (CSV)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
