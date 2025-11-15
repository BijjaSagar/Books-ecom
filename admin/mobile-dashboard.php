<?php
/**
 * admin/mobile-dashboard.php - Mobile-Responsive PWA Dashboard
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/AdminDashboard.php';

$dashboard = new AdminDashboard($conn);

// Get data
$today = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-7 days'));
$endDate = date('Y-m-d');

$salesMetrics = $dashboard->getSalesMetrics($startDate, $endDate);
$dailySales = $dashboard->getDailySalesData(7);
$topProducts = $dashboard->getTopProducts(5);
$orderStatus = $dashboard->getOrderStatusBreakdown($startDate, $endDate);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Bookory Admin">
    <meta name="theme-color" content="#007bff">
    <link rel="manifest" href="/public/manifest.json">
    <link rel="icon" type="image/png" href="/images/logo-192.png">
    <link rel="apple-touch-icon" href="/images/logo-192.png">

    <title>Mobile Dashboard - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        * {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
        }

        body {
            background-color: #f0f2f5;
            padding-top: 60px;
            padding-bottom: 80px;
            margin: 0;
            overflow-x: hidden;
        }

        /* Mobile Navigation */
        .mobile-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            border-bottom: 1px solid #dee2e6;
            z-index: 1000;
            padding: max(10px, env(safe-area-inset-top));
            padding-bottom: 10px;
        }

        .mobile-navbar h6 {
            margin: 0;
            font-weight: bold;
            color: #007bff;
        }

        /* Mobile Cards */
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            touch-action: manipulation;
        }

        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin: 5px 0;
        }

        .metric-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        /* Bottom Navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #dee2e6;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            z-index: 1000;
            display: flex;
            justify-content: space-around;
        }

        .nav-item-mobile {
            flex: 1;
            text-align: center;
            padding: 12px 0;
            text-decoration: none;
            color: #6c757d;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: color 0.3s;
        }

        .nav-item-mobile.active {
            color: #007bff;
        }

        .nav-item-mobile i {
            font-size: 20px;
        }

        /* Main Content */
        .mobile-content {
            padding: 15px;
            max-width: 100%;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 20px 0 10px 0;
            padding: 0 15px;
        }

        /* List items */
        .product-item {
            background: white;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-name {
            font-size: 13px;
            font-weight: 500;
            color: #333;
        }

        .product-meta {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }

        .badge-small {
            padding: 4px 8px;
            font-size: 10px;
        }

        /* Mini Charts */
        .mini-chart {
            height: 150px;
            margin: 10px 0;
        }

        /* Action buttons */
        .action-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 8px;
            cursor: pointer;
            font-weight: 500;
            touch-action: manipulation;
        }

        .btn-primary-mobile {
            background: #007bff;
            color: white;
        }

        .btn-secondary-mobile {
            background: #f0f2f5;
            color: #333;
        }

        /* Responsive tweaks */
        @media (max-height: 700px) {
            body {
                padding-bottom: 70px;
            }
        }

        /* Notch support */
        @supports (padding: max(0px)) {
            .mobile-navbar {
                padding-left: max(15px, env(safe-area-inset-left));
                padding-right: max(15px, env(safe-area-inset-right));
            }
        }

        /* Offline indicator */
        .offline-banner {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            display: none;
            border-bottom: 1px solid #ffc107;
        }

        .offline-banner.show {
            display: block;
        }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Offline Banner -->
    <div class="offline-banner" id="offlineBanner">
        <i class="bi bi-wifi-off"></i> You're offline - showing cached data
    </div>

    <!-- Mobile Header -->
    <div class="mobile-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <h6><i class="bi bi-speedometer2"></i> Bookory</h6>
            <button class="btn btn-sm btn-outline-secondary" id="syncBtn" title="Sync data">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Content -->
    <div class="mobile-content">
        <!-- Quick Stats -->
        <div class="row g-2">
            <div class="col-6">
                <div class="metric-card">
                    <div class="metric-label"><i class="bi bi-cash-flow"></i> Revenue (7d)</div>
                    <div class="metric-value">$<?php echo number_format($salesMetrics['total_revenue'] ?? 0, 0); ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="metric-card">
                    <div class="metric-label"><i class="bi bi-bag"></i> Orders (7d)</div>
                    <div class="metric-value"><?php echo $salesMetrics['total_orders'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="metric-card">
                    <div class="metric-label"><i class="bi bi-person-check"></i> Customers</div>
                    <div class="metric-value"><?php echo $salesMetrics['unique_customers'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="metric-card">
                    <div class="metric-label"><i class="bi bi-graph-up"></i> Avg Order</div>
                    <div class="metric-value">$<?php echo number_format(($salesMetrics['total_revenue'] ?? 0) / max(1, $salesMetrics['total_orders'] ?? 1), 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="section-title"><i class="bi bi-star"></i> Top Selling Products</div>
        <div style="background: white; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
            <?php foreach ($topProducts as $product): ?>
            <div class="product-item">
                <div>
                    <div class="product-name"><?php echo htmlspecialchars(substr($product['name'], 0, 25)); ?></div>
                    <div class="product-meta"><?php echo $product['units_sold']; ?> sold • $<?php echo number_format($product['total_revenue'] ?? 0, 0); ?></div>
                </div>
                <span class="badge bg-success badge-small"><?php echo round(($product['avg_price'] ?? 0), 0); ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Status -->
        <div class="section-title"><i class="bi bi-check-circle"></i> Order Status</div>
        <div style="background: white; border-radius: 12px; overflow: hidden; margin-bottom: 20px; padding: 15px;">
            <?php foreach ($orderStatus as $status): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size: 13px;"><?php echo ucfirst($status['order_status']); ?></span>
                <span class="badge bg-info"><?php echo $status['count']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="section-title"><i class="bi bi-lightning"></i> Quick Actions</div>
        <button class="action-btn btn-primary-mobile" onclick="window.location.href='products.php'">
            <i class="bi bi-plus"></i> Add Product
        </button>
        <button class="action-btn btn-primary-mobile" onclick="window.location.href='orders-management.php'">
            <i class="bi bi-truck"></i> View Orders
        </button>
        <button class="action-btn btn-secondary-mobile" onclick="window.location.href='inventory-management.php'">
            <i class="bi bi-box"></i> Check Inventory
        </button>
        <button class="action-btn btn-secondary-mobile" onclick="window.location.href='bulk-upload.php'">
            <i class="bi bi-cloud-upload"></i> Bulk Upload
        </button>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="mobile-dashboard.php" class="nav-item-mobile active" id="navHome">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="sales-dashboard.php" class="nav-item-mobile" id="navSales">
            <i class="bi bi-graph-up"></i>
            <span>Sales</span>
        </a>
        <a href="inventory-management.php" class="nav-item-mobile" id="navInventory">
            <i class="bi bi-box"></i>
            <span>Stock</span>
        </a>
        <a href="orders-management.php" class="nav-item-mobile" id="navOrders">
            <i class="bi bi-truck"></i>
            <span>Orders</span>
        </a>
        <a href="settings.php" class="nav-item-mobile" id="navSettings">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/public/service-worker.js')
                    .then(registration => {
                        console.log('Service Worker registered:', registration);
                    })
                    .catch(error => {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }

        // Offline detection
        window.addEventListener('offline', () => {
            document.getElementById('offlineBanner').classList.add('show');
        });

        window.addEventListener('online', () => {
            document.getElementById('offlineBanner').classList.remove('show');
        });

        // Sync button
        document.getElementById('syncBtn').addEventListener('click', () => {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.ready.then(registration => {
                    if ('sync' in registration) {
                        registration.sync.register('sync-data');
                    }
                    location.reload();
                });
            }
        });

        // Active navigation
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-item-mobile').forEach(item => {
            item.classList.remove('active');
        });

        if (currentPath.includes('mobile-dashboard')) {
            document.getElementById('navHome').classList.add('active');
        } else if (currentPath.includes('sales')) {
            document.getElementById('navSales').classList.add('active');
        } else if (currentPath.includes('inventory')) {
            document.getElementById('navInventory').classList.add('active');
        } else if (currentPath.includes('orders')) {
            document.getElementById('navOrders').classList.add('active');
        } else if (currentPath.includes('settings')) {
            document.getElementById('navSettings').classList.add('active');
        }

        // Prevent double tap zoom
        document.addEventListener('touchend', function (event) {
            if (event.changedTouches.length == 1) {
                if (event.changedTouches[0].clientX < window.innerWidth / 4 ||
                    event.changedTouches[0].clientX > window.innerWidth * 3 / 4) {
                    return;
                }
            }
        }, false);
    </script>
</body>
</html>
