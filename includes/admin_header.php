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
            --admin-primary: #1e40af;
            --admin-secondary: #1e3a8a;
            --admin-accent: #3b82f6;
            --admin-success: #10b981;
            --admin-danger: #ef4444;
            --admin-warning: #f59e0b;
            --admin-info: #06b6d4;
            --admin-dark: #111827;
            --admin-light: #f3f4f6;
            --admin-border: #e5e7eb;
        }

        * {
            transition: all 0.2s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            color: var(--admin-dark);
        }

        .admin-sidebar {
            background: #ffffff;
            border-right: 1px solid var(--admin-border);
            min-height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }

        .admin-sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--admin-border);
            text-align: center;
        }

        .sidebar-header h4 {
            color: var(--admin-primary);
            margin: 0;
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
        }

        .sidebar-header small {
            color: #6b7280;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            color: #4b5563;
            padding: 0.65rem 1.5rem;
            border-radius: 0;
            border-left: 3px solid transparent;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: var(--admin-primary);
            background: #f3f4f6;
            border-left-color: var(--admin-accent);
        }

        .nav-link.active {
            color: var(--admin-primary);
            background: #eff6ff;
            border-left-color: var(--admin-primary);
            font-weight: 600;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            background: #f9fafb;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        .admin-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--admin-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .page-header {
            background: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 5px solid var(--admin-primary);
        }

        .page-header h1 {
            color: var(--admin-dark);
            margin: 0;
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: #6b7280;
            margin: 0.5rem 0 0 0;
        }

        .page-content {
            padding: 0 2rem 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-left: 5px solid var(--admin-primary);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-card.bg-primary { border-left-color: var(--admin-primary); }
        .stat-card.bg-success { border-left-color: var(--admin-success); }
        .stat-card.bg-info { border-left-color: var(--admin-info); }
        .stat-card.bg-warning { border-left-color: var(--admin-warning); }
        .stat-card.bg-danger { border-left-color: var(--admin-danger); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .stat-icon.bg-primary { background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent)); }
        .stat-icon.bg-success { background: linear-gradient(135deg, var(--admin-success), #059669); }
        .stat-icon.bg-info { background: linear-gradient(135deg, var(--admin-info), #0891b2); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, var(--admin-warning), #d97706); }
        .stat-icon.bg-danger { background: linear-gradient(135deg, var(--admin-danger), #dc2626); }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--admin-dark);
            margin: 0.5rem 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background: white;
        }

        .table {
            margin: 0;
            background: white;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid var(--admin-border);
            font-weight: 600;
            color: var(--admin-dark);
            background: var(--admin-light);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid var(--admin-border);
        }

        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.55rem 1rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .alert {
            border: none;
            border-radius: 8px;
            border-left: 5px solid;
        }

        .alert-success {
            border-left-color: var(--admin-success);
            background: #f0fdf4;
            color: #166534;
        }
        .alert-danger {
            border-left-color: var(--admin-danger);
            background: #fef2f2;
            color: #991b1b;
        }
        .alert-warning {
            border-left-color: var(--admin-warning);
            background: #fffbeb;
            color: #92400e;
        }
        .alert-info {
            border-left-color: var(--admin-info);
            background: #ecf9ff;
            color: #164e63;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--admin-dark);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            font-size: 1.25rem;
        }

        .sidebar-toggle:hover {
            background: var(--admin-light);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 250px;
                margin-left: -250px;
            }

            .admin-sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .page-content {
                padding: 0 1rem 1rem;
            }
        }

        /* Animations */
        .nav-link {
            position: relative;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--admin-accent);
            border-radius: 0 3px 3px 0;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .nav-link:hover::before {
            opacity: 1;
        }

        .btn-sidebar-toggle {
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
                    <a class="nav-link <?php echo $current_page === 'menu_manager' ? 'active' : ''; ?>" href="/bookshelf/admin/menu_manager.php">
                        <i class="bi bi-list-nested"></i>
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
