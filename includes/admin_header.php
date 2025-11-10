<?php
// includes/admin_header.php - Modern admin header
session_start();

// Include database connection
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: /bookshelf/admin/index.php");
    exit();
}

// Get site settings
$settings_query = "SELECT setting_key, setting_value FROM site_settings";
$settings_result = $conn->query($settings_query);
$settings = [];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default settings if not found
$settings = array_merge([
    'site_name' => 'Bookory',
    'site_currency' => 'INR',
    'currency_symbol' => '₹'
], $settings);

// Get current page name for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #667eea;
            --admin-secondary: #764ba2;
            --admin-success: #10b981;
            --admin-danger: #ef4444;
            --admin-warning: #f59e0b;
            --admin-info: #3b82f6;
            --admin-dark: #1f2937;
            --admin-light: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--admin-light);
        }

        .admin-sidebar {
            background: linear-gradient(180deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .admin-sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .sidebar-header small {
            color: rgba(255,255,255,0.7);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: white;
        }

        .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: white;
            font-weight: 600;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: 280px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        .admin-navbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-header {
            background: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-primary);
        }

        .page-header h1 {
            color: var(--admin-dark);
            margin: 0;
            font-weight: 700;
        }

        .page-content {
            padding: 0 2rem 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-primary);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-card.bg-primary { border-left-color: var(--admin-primary); }
        .stat-card.bg-success { border-left-color: var(--admin-success); }
        .stat-card.bg-info { border-left-color: var(--admin-info); }
        .stat-card.bg-warning { border-left-color: var(--admin-warning); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.bg-primary { background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary)); }
        .stat-icon.bg-success { background: linear-gradient(135deg, var(--admin-success), #34d399); }
        .stat-icon.bg-info { background: linear-gradient(135deg, var(--admin-info), #60a5fa); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, var(--admin-warning), #fbbf24); }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .table {
            margin: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--admin-dark);
            background: var(--admin-light);
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5a67d8, #6b46c1);
            transform: translateY(-1px);
        }

        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-success { border-left-color: var(--admin-success); }
        .alert-danger { border-left-color: var(--admin-danger); }
        .alert-warning { border-left-color: var(--admin-warning); }
        .alert-info { border-left-color: var(--admin-info); }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--admin-dark);
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--admin-light);
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        .user-dropdown .dropdown-toggle::after {
            display: none;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--admin-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><?php echo htmlspecialchars($settings['site_name']); ?></h4>
            <small>Admin Panel</small>
        </div>
        
        <div class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'amazon-dashboard' ? 'active' : ''; ?>" href="/bookshelf/admin/amazon-dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>" href="/bookshelf/admin/orders.php">
                        <i class="bi bi-bag-check"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" href="/bookshelf/admin/products.php">
                        <i class="bi bi-book"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>" href="/bookshelf/admin/customers.php">
                        <i class="bi bi-people"></i>
                        <span>Customers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'categories' ? 'active' : ''; ?>" href="/bookshelf/admin/categories.php">
                        <i class="bi bi-tags"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="/bookshelf/admin/reports.php">
                        <i class="bi bi-graph-up"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'amazon-seller-dashboard' ? 'active' : ''; ?>" href="/bookshelf/admin/amazon-seller-dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Seller Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'amazon-inventory-manager' ? 'active' : ''; ?>" href="/bookshelf/admin/amazon-inventory-manager.php">
                        <i class="bi bi-box"></i>
                        <span>Inventory Manager</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'amazon-order-manager' ? 'active' : ''; ?>" href="/bookshelf/admin/amazon-order-manager.php">
                        <i class="bi bi-bag-check"></i>
                        <span>Order Manager</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'amazon-performance-reports' ? 'active' : ''; ?>" href="/bookshelf/admin/amazon-performance-reports.php">
                        <i class="bi bi-bar-chart"></i>
                        <span>Performance Reports</span>
                    </a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'menu_manager' ? 'active' : ''; ?>" href="/bookshelf/admin/menu_manager.php">

                        <i class="bi bi-gear"></i>
                        <span>Menu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" href="/bookshelf/admin/settings.php">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Top Navigation -->
        <nav class="admin-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="sidebar-toggle me-3" id="sidebar-toggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h5 class="mb-0 text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></h5>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown user-dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/bookshelf/"><i class="bi bi-house me-2"></i>View Website</a></li>
                            <li><a class="dropdown-item" href="/bookshelf/admin/settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/bookshelf/admin/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="page-content">
