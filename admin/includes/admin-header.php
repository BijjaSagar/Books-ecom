<?php
/**
 * Professional Admin Header Template
 * Books & eBooks eCommerce Platform
 *
 * This file provides the professional header, sidebar navigation,
 * and layout structure for all admin pages
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_email = $_SESSION['admin_email'] ?? 'admin@bookory.com';

// Get site settings
$site_name = 'Bookory';
if (isset($conn)) {
    $settings_query = "SELECT setting_key, setting_value FROM site_settings LIMIT 10";
    $settings_result = $conn->query($settings_query);
    if ($settings_result) {
        while ($row = $settings_result->fetch_assoc()) {
            if ($row['setting_key'] === 'site_name') {
                $site_name = $row['setting_value'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a3a52">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin Panel'); ?> - <?php echo htmlspecialchars($site_name); ?></title>

    <!-- Admin Theme CSS -->
    <link rel="stylesheet" href="<?php echo dirname(__DIR__); ?>/css/admin-theme.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Additional Styles -->
    <style>
        /* Custom overrides for specific pages */
        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- SIDEBAR NAVIGATION -->
        <aside class="admin-sidebar">
            <!-- Brand Section -->
            <div class="sidebar-brand">
                <h3>
                    <i class="bi bi-book-fill"></i>
                    <?php echo htmlspecialchars($site_name); ?>
                </h3>
                <small style="color: rgba(255,255,255,0.5);">Admin Panel</small>
            </div>

            <!-- Main Navigation Menu -->
            <ul class="sidebar-menu">
                <!-- Dashboard -->
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Products Section -->
                <li>
                    <a href="products.php" class="<?php echo $current_page === 'products.php' || $current_page === 'product-add.php' ? 'active' : ''; ?>">
                        <i class="bi bi-book"></i>
                        <span>Products</span>
                    </a>
                </li>

                <!-- Categories -->
                <li>
                    <a href="categories.php" class="<?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                        <i class="bi bi-list-ul"></i>
                        <span>Categories</span>
                    </a>
                </li>

                <!-- Orders -->
                <li>
                    <a href="orders.php" class="<?php echo $current_page === 'orders.php' || $current_page === 'order-details.php' ? 'active' : ''; ?>">
                        <i class="bi bi-bag-check"></i>
                        <span>Orders</span>
                    </a>
                </li>

                <!-- Customers -->
                <li>
                    <a href="customers.php" class="<?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span>Customers</span>
                    </a>
                </li>

                <!-- Reports -->
                <li>
                    <a href="reports.php" class="<?php echo $current_page === 'reports.php' || $current_page === 'sales-dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <!-- Settings -->
                <li>
                    <a href="settings.php" class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>

                <!-- Divider -->
                <li style="border-top: 1px solid rgba(255,255,255,0.2); margin-top: 1rem;"></li>

                <!-- Logout -->
                <li>
                    <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="admin-main">
            <!-- HEADER -->
            <header class="admin-header">
                <div class="header-left">
                    <div class="header-breadcrumb">
                        <i class="bi bi-house-door"></i>
                        <span><?php echo htmlspecialchars($page_title ?? 'Admin'); ?></span>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Search (Optional) -->
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="position: relative;">
                            <input type="text" placeholder="Search..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; width: 200px; font-size: 13px;">
                            <i class="bi bi-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="user-profile" style="position: relative;">
                            <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <strong style="font-size: 13px; color: #2c3e50;"><?php echo htmlspecialchars($admin_name); ?></strong>
                                <small style="font-size: 12px; color: #7f8c8d;">Administrator</small>
                            </div>
                            <i class="bi bi-chevron-down" style="color: #7f8c8d; cursor: pointer;"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT -->
            <main class="admin-content">
