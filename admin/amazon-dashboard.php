<?php
include '../includes/admin_header.php';

// Get key statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$published_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'published'")->fetch_assoc()['count'];
$draft_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'draft'")->fetch_assoc()['count'];

$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'")->fetch_assoc()['count'];
$processing_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'processing'")->fetch_assoc()['count'];

$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')")->fetch_assoc()['total'] ?? 0;
$today_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered') AND DATE(created_at) = CURDATE()")->fetch_assoc()['total'] ?? 0;

$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$new_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Get recent orders
$recent_orders_stmt = $conn->prepare("SELECT o.*, COALESCE(CONCAT(o.first_name, ' ', o.last_name), 'Guest') as customer_name FROM orders o ORDER BY o.created_at DESC LIMIT 5");
$recent_orders_stmt->execute();
$recent_orders_result = $recent_orders_stmt->get_result();

// Get low stock products
$low_stock_stmt = $conn->prepare("SELECT * FROM products WHERE stock_quantity <= 10 AND stock_quantity > 0 ORDER BY stock_quantity ASC LIMIT 5");
$low_stock_stmt->execute();
$low_stock_result = $low_stock_stmt->get_result();
?>

<style>
:root {
    --amazon-dark: #232f3e;
    --amazon-primary: #146eb4;
    --amazon-accent: #ff9900;
    --amazon-success: #28a745;
    --amazon-warning: #ffc107;
    --amazon-danger: #dc3545;
    --amazon-light: #f0f2f4;
    --border-radius: 8px;
    --box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

body {
    background: var(--amazon-light);
    font-family: 'Inter', sans-serif;
}

.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.dashboard-header {
    background: linear-gradient(135deg, var(--amazon-dark) 0%, var(--amazon-primary) 100%);
    color: white;
    border-radius: var(--border-radius);
    padding: 32px;
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.header-content {
    position: relative;
    z-index: 2;
}

.header-content h1 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 24px;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
    border-left: 4px solid var(--amazon-primary);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.stat-card.products {
    border-left-color: var(--amazon-primary);
}

.stat-card.orders {
    border-left-color: var(--amazon-accent);
}

.stat-card.revenue {
    border-left-color: var(--amazon-success);
}

.stat-card.customers {
    border-left-color: var(--amazon-warning);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 16px;
}

.stat-icon.products {
    background: linear-gradient(135deg, var(--amazon-primary) 0%, #1a73e8 100%);
    color: white;
}

.stat-icon.orders {
    background: linear-gradient(135deg, var(--amazon-accent) 0%, #ffa726 100%);
    color: white;
}

.stat-icon.revenue {
    background: linear-gradient(135deg, var(--amazon-success) 0%, #2e7d32 100%);
    color: white;
}

.stat-icon.customers {
    background: linear-gradient(135deg, var(--amazon-warning) 0%, #f57c00 100%);
    color: white;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--amazon-dark);
}

.stat-label {
    font-size: 1rem;
    color: #666;
    margin: 0 0 16px 0;
    font-weight: 500;
}

.stat-change {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-change.positive {
    color: var(--amazon-success);
}

.stat-change.negative {
    color: var(--amazon-danger);
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
}

.card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
}

.card-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--amazon-dark);
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 0;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    text-align: left;
    padding: 16px 24px;
    font-weight: 600;
    color: #555;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 16px 24px;
    border-bottom: 1px solid #f5f5f5;
    font-size: 0.95rem;
}

.table tr:last-child td {
    border-bottom: none;
}

.table tr:hover td {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background: #fff8e1;
    color: #f57f17;
}

.status-processing {
    background: #e3f2fd;
    color: #1976d2;
}

.status-shipped {
    background: #e8f5e9;
    color: #388e3c;
}

.status-delivered {
    background: #e8f5e9;
    color: #388e3c;
}

.status-cancelled {
    background: #ffebee;
    color: #d32f2f;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--amazon-primary);
    color: white;
}

.btn-primary:hover {
    background: #0d5a9e;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--amazon-primary);
    color: var(--amazon-primary);
}

.btn-outline:hover {
    background: var(--amazon-primary);
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
}

.view-all {
    text-align: center;
    padding: 20px;
    border-top: 1px solid #eee;
}

.view-all a {
    color: var(--amazon-primary);
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.view-all a:hover {
    text-decoration: underline;
}

@media (max-width: 992px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 16px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .table {
        font-size: 0.85rem;
    }
    
    .table th,
    .table td {
        padding: 12px 16px;
    }
}
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="header-content">
            <div>
                <h1>
                    <span>🏪</span> Seller Central Dashboard
                </h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>. Here's what's happening with your store today.</p>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card products">
            <div class="stat-icon products">📚</div>
            <h2 class="stat-value"><?php echo number_format($total_products); ?></h2>
            <div class="stat-label">Total Products</div>
            <div class="stat-change positive">
                <span>✅</span> <?php echo number_format($published_products); ?> published
            </div>
        </div>
        
        <div class="stat-card orders">
            <div class="stat-icon orders">📦</div>
            <h2 class="stat-value"><?php echo number_format($total_orders); ?></h2>
            <div class="stat-label">Total Orders</div>
            <div class="stat-change positive">
                <span>⏱️</span> <?php echo number_format($pending_orders); ?> pending
            </div>
        </div>
        
        <div class="stat-card revenue">
            <div class="stat-icon revenue">💰</div>
            <h2 class="stat-value">₹<?php echo number_format($total_revenue); ?></h2>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-change positive">
                <span>📈</span> ₹<?php echo number_format($today_revenue); ?> today
            </div>
        </div>
        
        <div class="stat-card customers">
            <div class="stat-icon customers">👥</div>
            <h2 class="stat-value"><?php echo number_format($total_customers); ?></h2>
            <div class="stat-label">Total Customers</div>
            <div class="stat-change positive">
                <span>🆕</span> <?php echo number_format($new_customers); ?> this week
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h3>📦 Recent Orders</h3>
            </div>
            <div class="card-body">
                <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <div style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    </td>
                                    <td>
                                        <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'Pending',
                                                'processing' => 'Processing',
                                                'shipped' => 'Shipped',
                                                'delivered' => 'Delivered',
                                                'cancelled' => 'Cancelled'
                                            ];
                                            echo $status_labels[$order['order_status']] ?? ucfirst($order['order_status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                            <span>👁️</span> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #777;">
                        <div style="font-size: 3rem; margin-bottom: 16px;">📭</div>
                        <h4>No orders yet</h4>
                        <p>Orders will appear here when customers make purchases</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="view-all">
                <a href="amazon-order-manager.php">
                    <span>📋</span> View all orders
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>⚠️ Low Stock Alert</h3>
            </div>
            <div class="card-body">
                <?php if ($low_stock_result && $low_stock_result->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $low_stock_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($product['author']); ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: #ff9800;"><?php echo $product['stock_quantity']; ?></strong>
                                    </td>
                                    <td>
                                        <a href="products-professional.php" class="btn btn-outline btn-sm">
                                            <span>✏️</span> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: #777;">
                        <div style="font-size: 3rem; margin-bottom: 16px;">✅</div>
                        <h4>All stock levels good</h4>
                        <p>No low stock alerts at this time</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="view-all">
                <a href="amazon-inventory-manager.php">
                    <span>📚</span> Manage inventory
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="card-body" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <a href="amazon-inventory-manager.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px;">
                    <span>➕</span> Add New Product
                </a>
                <a href="amazon-order-manager.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px;">
                    <span>📦</span> Manage Orders
                </a>
                <a href="amazon-performance-reports.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px;">
                    <span>📈</span> View Reports
                </a>
                <a href="settings-professional.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px;">
                    <span>⚙️</span> Settings
                </a>
            </div>
        </div>
    </div>
</div>