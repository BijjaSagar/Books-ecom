<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/admin_header.php'; 

// Enhanced data collection for professional dashboard
function getAdvancedStats($conn) {
    $stats = [];
    
    // Revenue Analytics
    $monthly_revenue = $conn->query("SELECT MONTH(created_at) as month, SUM(total_amount) as revenue FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND order_status IN ('completed', 'delivered') GROUP BY MONTH(created_at) ORDER BY month");
    $stats['monthly_revenue'] = [];
    if ($monthly_revenue) {
        while ($row = $monthly_revenue->fetch_assoc()) {
            $stats['monthly_revenue'][$row['month']] = $row['revenue'];
        }
    }
    
    // Weekly sales comparison
    $this_week = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE WEEK(created_at) = WEEK(CURDATE()) AND order_status IN ('completed', 'delivered')");
    $last_week = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE WEEK(created_at) = WEEK(CURDATE()) - 1 AND order_status IN ('completed', 'delivered')");
    $stats['this_week'] = $this_week ? ($this_week->fetch_assoc()['total'] ?? 0) : 0;
    $stats['last_week'] = $last_week ? ($last_week->fetch_assoc()['total'] ?? 0) : 0;
    
    // Top selling products
    $top_products = $conn->query("SELECT p.title, SUM(oi.quantity) as sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id ORDER BY sold DESC LIMIT 5");
    $stats['top_products'] = [];
    if ($top_products) {
        while ($row = $top_products->fetch_assoc()) {
            $stats['top_products'][] = $row;
        }
    }
    
    // Category performance
    $category_sales = $conn->query("SELECT c.name, COUNT(oi.id) as orders FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN categories c ON p.category_id = c.id GROUP BY c.id ORDER BY orders DESC LIMIT 5");
    $stats['category_sales'] = [];
    if ($category_sales) {
        while ($row = $category_sales->fetch_assoc()) {
            $stats['category_sales'][] = $row;
        }
    }
    
    // Customer insights
    $new_customers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stats['new_customers'] = $new_customers ? ($new_customers->fetch_assoc()['total'] ?? 0) : 0;
    
    return $stats;
}

$advanced_stats = getAdvancedStats($conn);
?>

<!-- Professional E-commerce Dashboard CSS -->
<style>
    :root {
        --primary: #667eea;
        --primary-dark: #5a6fd8;
        --secondary: #764ba2;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #343a40;
        --border-radius: 12px;
        --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        box-sizing: border-box;
    }
    
    body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        color: #374151;
    }
    
    .dashboard-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
        min-height: 100vh;
    }
    
    .dashboard-header {
        background: white;
        border-radius: var(--border-radius);
        padding: 32px;
        box-shadow: var(--box-shadow);
        margin-bottom: 32px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50%, -50%);
    }
    
    .header-content {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .header-title h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .header-title p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .header-stats {
        display: flex;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .header-stat {
        text-align: center;
        background: rgba(255, 255, 255, 0.15);
        padding: 16px 24px;
        border-radius: 12px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .header-stat h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 700;
    }
    
    .header-stat p {
        margin: 4px 0 0 0;
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .analytics-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .chart-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
    }
    
    .card-header {
        padding: 24px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    
    .card-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .card-body {
        padding: 24px;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .metric-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 24px;
        box-shadow: var(--box-shadow);
        position: relative;
        overflow: hidden;
        transition: var(--transition);
        border-left: 4px solid transparent;
    }
    
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.12);
    }
    
    .metric-card.revenue {
        border-left-color: var(--primary);
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    }
    
    .metric-card.orders {
        border-left-color: var(--success);
        background: linear-gradient(135deg, #28a74510 0%, #20c99710 100%);
    }
    
    .metric-card.products {
        border-left-color: var(--info);
        background: linear-gradient(135deg, #17a2b810 0%, #138a9b10 100%);
    }
    
    .metric-card.customers {
        border-left-color: var(--warning);
        background: linear-gradient(135deg, #ffc10710 0%, #e0a80010 100%);
    }
    
    .metric-icon {
        width: 64px;
        height: 64px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 16px;
        position: relative;
    }
    
    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        color: #1f2937;
    }
    
    .metric-label {
        font-size: 1rem;
        color: #6b7280;
        margin: 4px 0;
        font-weight: 500;
    }
    
    .metric-change {
        font-size: 0.875rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
    }
    
    .metric-change.positive {
        color: var(--success);
    }
    
    .metric-change.negative {
        color: var(--danger);
    }
    
    .content-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    .data-table th {
        background: #f8fafc;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .data-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        vertical-align: middle;
    }
    
    .data-table tr:hover {
        background: #f8fafc;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .quick-actions {
        display: grid;
        gap: 12px;
    }
    
    .action-btn {
        padding: 16px;
        text-decoration: none;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        gap: 12px;
        color: white;
        font-weight: 500;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
    }
    
    .action-btn:hover::before {
        left: 100%;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.2);
        color: white;
        text-decoration: none;
    }
    
    .chart-placeholder {
        height: 300px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 1.1rem;
        border: 2px dashed #d1d5db;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 4px;
        transition: width 1s ease;
    }
    
    .insight-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--box-shadow);
        margin-bottom: 16px;
        border-left: 4px solid var(--primary);
    }
    
    .insight-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0 0 8px 0;
        color: #1f2937;
    }
    
    .insight-description {
        color: #6b7280;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .alert-banner {
        background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
        border: 1px solid #f59e0b;
        color: #92400e;
        padding: 16px 24px;
        border-radius: var(--border-radius);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--box-shadow);
    }
    
    .notification-dot {
        width: 8px;
        height: 8px;
        background: var(--danger);
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .analytics-grid,
        .content-section {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 16px;
        }
        
        .dashboard-header {
            padding: 24px 20px;
        }
        
        .header-content {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .header-title h1 {
            font-size: 2rem;
        }
        
        .header-stats {
            width: 100%;
            justify-content: space-between;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
        
        .metric-value {
            font-size: 2rem;
        }
        
        .data-table {
            font-size: 0.875rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 8px;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-container {
            padding: 12px;
        }
        
        .header-stats {
            flex-direction: column;
            gap: 12px;
        }
        
        .header-stat {
            width: 100%;
        }
        
        .metric-card {
            padding: 16px;
        }
        
        .card-header,
        .card-body {
            padding: 16px;
        }
    }
</style>

<!-- Professional E-commerce Dashboard -->
<div class="dashboard-container">

<?php
// Get statistics with error handling
function getSafeStat($conn, $query, $default = 0) {
    try {
        $result = $conn->query($query);
        return $result ? $result->fetch_assoc()['total'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Basic statistics
$total_orders = getSafeStat($conn, "SELECT COUNT(id) as total FROM orders");
$total_revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')";
$total_revenue_result = $conn->query($total_revenue_query);
$total_revenue = $total_revenue_result ? ($total_revenue_result->fetch_assoc()['total'] ?? 0) : 0;
$total_products = getSafeStat($conn, "SELECT COUNT(id) as total FROM products");
$total_customers = getSafeStat($conn, "SELECT COUNT(id) as total FROM users WHERE role = 'customer'");

// Today's statistics
$today_orders = getSafeStat($conn, "SELECT COUNT(id) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$today_revenue_query = "SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE() AND order_status IN ('completed', 'delivered')";
$today_revenue_result = $conn->query($today_revenue_query);
$today_revenue = $today_revenue_result ? ($today_revenue_result->fetch_assoc()['total'] ?? 0) : 0;

// Pending orders
$pending_orders = getSafeStat($conn, "SELECT COUNT(id) as total FROM orders WHERE order_status = 'pending'");

// Low stock products
$low_stock = getSafeStat($conn, "SELECT COUNT(id) as total FROM products WHERE stock_quantity <= 10");

// Calculate growth percentages
$revenue_growth = 0;
if ($advanced_stats['last_week'] > 0) {
    $revenue_growth = (($advanced_stats['this_week'] - $advanced_stats['last_week']) / $advanced_stats['last_week']) * 100;
}

// Recent orders
$recent_orders_query = "SELECT o.*, COALESCE(o.first_name, 'Guest') as customer_name 
                       FROM orders o 
                       ORDER BY o.created_at DESC LIMIT 8";
$recent_orders = $conn->query($recent_orders_query);

// Order status distribution
$status_query = "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status";
$status_data = $conn->query($status_query);
$status_stats = [];
if ($status_data) {
    while ($row = $status_data->fetch_assoc()) {
        $status_stats[$row['order_status']] = $row['count'];
    }
}
?>

    <!-- Success Message -->
    <?php if (isset($_SESSION['setup_message'])): ?>
        <div class="alert-banner">
            <div style="font-size: 1.5rem;">✅</div>
            <div>
                <strong><?php echo $_SESSION['setup_message']; unset($_SESSION['setup_message']); ?></strong>
            </div>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-title">
                <h1>📊 E-commerce Dashboard</h1>
                <p>Real-time insights and analytics for your bookstore</p>
            </div>
            <div class="header-stats">
                <div class="header-stat">
                    <h3><?php echo number_format($today_orders); ?></h3>
                    <p>Today's Orders</p>
                </div>
                <div class="header-stat">
                    <h3><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($today_revenue, 0); ?></h3>
                    <p>Today's Revenue</p>
                </div>
                <div class="header-stat">
                    <h3><?php echo $advanced_stats['new_customers']; ?></h3>
                    <p>New Customers (30d)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($pending_orders > 0 || $low_stock > 0): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-bottom: 32px;">
            <?php if ($pending_orders > 0): ?>
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%); border: 1px solid #f59e0b; color: #92400e; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <div class="notification-dot"></div>
                    <div style="font-size: 1.5rem;">⚠️</div>
                    <div style="flex-grow: 1;">
                        <strong><?php echo $pending_orders; ?> Pending Orders</strong>
                        <br><small>Require immediate attention</small>
                    </div>
                    <a href="orders.php?status=pending" style="background: #f59e0b; color: white; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500;">Review</a>
                </div>
            <?php endif; ?>
            
            <?php if ($low_stock > 0): ?>
                <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 1px solid #ef4444; color: #dc2626; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <div class="notification-dot"></div>
                    <div style="font-size: 1.5rem;">📦</div>
                    <div style="flex-grow: 1;">
                        <strong><?php echo $low_stock; ?> Low Stock Items</strong>
                        <br><small>Need restocking soon</small>
                    </div>
                    <a href="products.php?low_stock=1" style="background: #ef4444; color: white; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500;">Restock</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Key Metrics Grid -->
    <div class="metrics-grid">
        <!-- Revenue Metric -->
        <div class="metric-card revenue">
            <div class="metric-icon" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white;">
                💰
            </div>
            <h2 class="metric-value"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($total_revenue, 0); ?></h2>
            <p class="metric-label">Total Revenue</p>
            <div class="metric-change <?php echo $revenue_growth >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $revenue_growth >= 0 ? '↗️' : '↘️'; ?>
                <?php echo abs(number_format($revenue_growth, 1)); ?>% vs last week
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(($today_revenue / max($total_revenue / 30, 1)) * 100, 100); ?>%;"></div>
            </div>
        </div>

        <!-- Orders Metric -->
        <div class="metric-card orders">
            <div class="metric-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%); color: white;">
                🛍️
            </div>
            <h2 class="metric-value"><?php echo number_format($total_orders); ?></h2>
            <p class="metric-label">Total Orders</p>
            <div class="metric-change positive">
                🔥 <?php echo $today_orders; ?> orders today
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(($today_orders / max($total_orders / 30, 1)) * 100, 100); ?>%;"></div>
            </div>
        </div>

        <!-- Products Metric -->
        <div class="metric-card products">
            <div class="metric-icon" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%); color: white;">
                📚
            </div>
            <h2 class="metric-value"><?php echo number_format($total_products); ?></h2>
            <p class="metric-label">Total Products</p>
            <div class="metric-change <?php echo $low_stock > 0 ? 'negative' : 'positive'; ?>">
                <?php echo $low_stock > 0 ? '⚠️' : '✅'; ?>
                <?php echo $low_stock; ?> low stock items
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo max(100 - ($low_stock / max($total_products, 1)) * 100, 0); ?>%;"></div>
            </div>
        </div>

        <!-- Customers Metric -->
        <div class="metric-card customers">
            <div class="metric-icon" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%); color: white;">
                👥
            </div>
            <h2 class="metric-value"><?php echo number_format($total_customers); ?></h2>
            <p class="metric-label">Total Customers</p>
            <div class="metric-change positive">
                ✨ <?php echo $advanced_stats['new_customers']; ?> new this month
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo min(($advanced_stats['new_customers'] / max($total_customers, 1)) * 100, 100); ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="analytics-grid">
        <!-- Sales Chart -->
        <div class="chart-card">
            <div class="card-header">
                <h3>📈 Revenue Analytics</h3>
            </div>
            <div class="card-body">
                <div class="chart-placeholder">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 16px;">📉</div>
                        <h4 style="margin: 0 0 8px 0; color: #374151;">Monthly Revenue Trends</h4>
                        <p style="margin: 0; font-size: 0.9rem;">Chart visualization coming soon</p>
                        <div style="margin-top: 16px; padding: 16px; background: white; border-radius: 8px; text-align: left;">
                            <strong style="color: var(--primary);">Monthly Revenue:</strong>
                            <div style="margin-top: 8px; font-size: 0.9rem;">
                                <?php 
                                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                foreach ($advanced_stats['monthly_revenue'] as $month => $revenue): 
                                ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <span><?php echo $months[$month-1]; ?>:</span>
                                    <span style="font-weight: 600;"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($revenue, 0); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="chart-card">
            <div class="card-header">
                <h3>🏆 Performance Insights</h3>
            </div>
            <div class="card-body">
                <!-- Top Products -->
                <div class="insight-card">
                    <h4 class="insight-title">🏅 Top Selling Products</h4>
                    <?php if (!empty($advanced_stats['top_products'])): ?>
                        <?php foreach (array_slice($advanced_stats['top_products'], 0, 3) as $product): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding: 8px; background: #f8fafc; border-radius: 6px;">
                                <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($product['title']); ?></span>
                                <span style="background: var(--primary); color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;"><?php echo $product['sold']; ?> sold</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="insight-description">No sales data available yet</p>
                    <?php endif; ?>
                </div>

                <!-- Category Performance -->
                <div class="insight-card" style="border-left-color: var(--success);">
                    <h4 class="insight-title">📋 Category Performance</h4>
                    <?php if (!empty($advanced_stats['category_sales'])): ?>
                        <?php foreach (array_slice($advanced_stats['category_sales'], 0, 3) as $category): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding: 8px; background: #f0fdf4; border-radius: 6px;">
                                <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($category['name']); ?></span>
                                <span style="background: var(--success); color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;"><?php echo $category['orders']; ?> orders</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="insight-description">No category data available yet</p>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="insight-card" style="border-left-color: var(--info);">
                    <h4 class="insight-title">⚡ Quick Stats</h4>
                    <div style="font-size: 0.9rem; line-height: 1.6;">
                        <div>Average order value: <strong><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00'; ?></strong></div>
                        <div>Orders today: <strong><?php echo $today_orders; ?></strong></div>
                        <div>Revenue today: <strong><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($today_revenue, 0); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content Section -->
    <div class="content-section">
        <!-- Recent Orders Table -->
        <div class="chart-card">
            <div class="card-header">
                <h3>🕰️ Recent Orders</h3>
                <a href="orders.php" style="background: var(--primary); color: white; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-size: 0.9rem; font-weight: 500; transition: var(--transition);">View All Orders</a>
            </div>
            <div>
                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary);">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                                <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                                <div style="font-size: 0.8rem; color: #6b7280;"><?php echo htmlspecialchars($order['email'] ?? 'No email'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500;"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                        <div style="font-size: 0.8rem; color: #6b7280;"><?php echo date('H:i', strtotime($order['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--success);"><?php echo $settings['currency_symbol'] ?? '₹'; ?><?php echo number_format($order['total_amount'], 2); ?></div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'background: #fef3c7; color: #92400e;',
                                            'processing' => 'background: #dbeafe; color: #1e40af;',
                                            'shipped' => 'background: #e0e7ff; color: #5b21b6;',
                                            'delivered' => 'background: #d1fae5; color: #065f46;',
                                            'completed' => 'background: #d1fae5; color: #065f46;',
                                            'cancelled' => 'background: #fee2e2; color: #991b1b;'
                                        ];
                                        $status = $order['order_status'] ?? 'pending';
                                        $style = $status_colors[$status] ?? 'background: #f3f4f6; color: #374151;';
                                        ?>
                                        <span class="status-badge" style="<?php echo $style; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="orders.php?view=<?php echo $order['id']; ?>" style="background: var(--info); color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-size: 0.8rem;">View</a>
                                            <?php if ($status === 'pending'): ?>
                                                <a href="orders.php?process=<?php echo $order['id']; ?>" style="background: var(--success); color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-size: 0.8rem;">Process</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 2rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">📦</div>
                        <h4 style="color: #6b7280; margin: 1rem 0;">No orders yet</h4>
                        <p style="color: #9ca3af; margin: 0;">Orders will appear here once customers start purchasing</p>
                        <a href="products.php" style="display: inline-block; margin-top: 1.5rem; background: var(--primary); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 500;">Add Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Sidebar Content -->
        <div>
            <!-- Quick Actions Panel -->
            <div class="chart-card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="products.php" class="action-btn" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);">
                            <span style="font-size: 1.2rem;">📚</span>
                            <span>Manage Products</span>
                        </a>
                        <a href="orders.php" class="action-btn" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                            <span style="font-size: 1.2rem;">🛍️</span>
                            <span>Process Orders</span>
                        </a>
                        <a href="customers.php" class="action-btn" style="background: linear-gradient(135deg, var(--info) 0%, #138a9b 100%);">
                            <span style="font-size: 1.2rem;">👥</span>
                            <span>Customer Management</span>
                        </a>
                        <a href="categories.php" class="action-btn" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%);">
                            <span style="font-size: 1.2rem;">🏷️</span>
                            <span>Categories</span>
                        </a>
                        <a href="reports.php" class="action-btn" style="background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);">
                            <span style="font-size: 1.2rem;">📊</span>
                            <span>Analytics & Reports</span>
                        </a>
                        <a href="settings.php" class="action-btn" style="background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);">
                            <span style="font-size: 1.2rem;">⚙️</span>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
            </div>
                    <a href="products.php" class="action-btn" style="background: #667eea;">
                        <span style="margin-right: 0.5rem;">📚</span> Add New Product
                    </a>
                    <a href="orders.php" class="action-btn" style="background: #28a745;">
                        <span style="margin-right: 0.5rem;">🛍️</span> Manage Orders
                    </a>
                    <a href="customers.php" class="action-btn" style="background: #17a2b8;">
                        <span style="margin-right: 0.5rem;">👥</span> View Customers
                    </a>
                    <a href="categories.php" class="action-btn" style="background: #6c757d;">
                        <span style="margin-right: 0.5rem;">🏷️</span> Categories
                    </a>
                    <a href="reports.php" class="action-btn" style="background: #ffc107;">
                        <span style="margin-right: 0.5rem;">📊</span> View Reports
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Order Status Overview -->
        <div class="chart-card">
            <div class="card-header">
                <h3>📊 Order Status Overview</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($status_stats)): ?>
                    <?php 
                    $status_config = [
                        'pending' => ['color' => '#fef3c7', 'text' => '#92400e', 'icon' => '🕰️'],
                        'processing' => ['color' => '#dbeafe', 'text' => '#1e40af', 'icon' => '⚙️'],
                        'shipped' => ['color' => '#e0e7ff', 'text' => '#5b21b6', 'icon' => '🚚'],
                        'delivered' => ['color' => '#d1fae5', 'text' => '#065f46', 'icon' => '✅'],
                        'completed' => ['color' => '#d1fae5', 'text' => '#065f46', 'icon' => '✨'],
                        'cancelled' => ['color' => '#fee2e2', 'text' => '#991b1b', 'icon' => '❌']
                    ];
                    ?>
                    <?php foreach ($status_stats as $status => $count): ?>
                        <?php $config = $status_config[$status] ?? ['color' => '#f3f4f6', 'text' => '#374151', 'icon' => '📋']; ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 12px; background: <?php echo $config['color']; ?>; border-radius: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 1.2rem;"><?php echo $config['icon']; ?></span>
                                <div>
                                    <div style="font-weight: 600; color: <?php echo $config['text']; ?>; text-transform: capitalize;"><?php echo $status; ?></div>
                                    <div style="font-size: 0.8rem; color: <?php echo $config['text']; ?>; opacity: 0.8;">Orders</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: <?php echo $config['text']; ?>;"><?php echo $count; ?></div>
                                <div style="font-size: 0.7rem; color: <?php echo $config['text']; ?>; opacity: 0.8;"><?php echo number_format(($count / max(array_sum($status_stats), 1)) * 100, 1); ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #374151;">Total Orders:</span>
                            <span style="font-size: 1.2rem; font-weight: 700; color: var(--primary);"><?php echo array_sum($status_stats); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📋</div>
                        <p style="margin: 0; font-style: italic;">No order data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Professional Welcome Section for New Users -->
    <?php if ($total_orders == 0 && $total_products == 0): ?>
        <div style="background: white; border-radius: 16px; padding: 3rem; text-align: center; box-shadow: var(--box-shadow); margin-top: 32px;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem;">🚀</div>
            <h3 style="font-size: 2rem; margin: 0 0 1rem 0; color: #1f2937;">Welcome to Your Professional Dashboard!</h3>
            <p style="color: #6b7280; font-size: 1.1rem; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto;">Get started by setting up your store with products and categories. Your journey to e-commerce success begins here!</p>
            <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                <a href="products.php" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <span>📚</span> Add Your First Product
                </a>
                <a href="categories.php" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <span>🏷️</span> Setup Categories
                </a>
                <a href="settings.php" style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; padding: 16px 32px; text-decoration: none; border-radius: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: var(--transition);">
                    <span>⚙️</span> Configure Settings
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Add JavaScript for enhanced interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate metric cards on load
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    setTimeout(() => {
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            bar.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 500);

    // Add hover effects to action buttons
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Real-time clock in header
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        const dateString = now.toLocaleDateString();
        
        // Add time display to header if it doesn't exist
        const headerStats = document.querySelector('.header-stats');
        if (headerStats && !document.querySelector('.live-time')) {
            const timeDiv = document.createElement('div');
            timeDiv.className = 'header-stat live-time';
            timeDiv.innerHTML = `
                <h3>${timeString}</h3>
                <p>${dateString}</p>
            `;
            headerStats.appendChild(timeDiv);
        } else if (document.querySelector('.live-time h3')) {
            document.querySelector('.live-time h3').textContent = timeString;
        }
    }
    
    updateTime();
    setInterval(updateTime, 1000);

    // Add loading states to action buttons
    actionBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Don't prevent default, but add loading effect
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
            
            // Reset after 2 seconds in case of navigation issues
            setTimeout(() => {
                this.style.opacity = '1';
                this.style.pointerEvents = 'auto';
            }, 2000);
        });
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>