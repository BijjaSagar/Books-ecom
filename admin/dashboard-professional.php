<?php
/**
 * Professional Admin Dashboard
 * Books & eBooks eCommerce Platform
 *
 * Features:
 * - Key metrics and statistics
 * - Recent orders and products
 * - Sales overview
 * - Inventory alerts
 * - Clean, minimal design
 */

$page_title = 'Dashboard';
require_once 'includes/admin-header.php';

// Check for setup message
$setup_message = $_SESSION['setup_message'] ?? '';
if ($setup_message) {
    unset($_SESSION['setup_message']);
}

// Get dashboard statistics
$stats = [
    'total_products' => 0,
    'featured_products' => 0,
    'total_orders' => 0,
    'total_customers' => 0,
    'low_stock_count' => 0,
    'total_revenue' => 0
];

// Total Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_products'] = $row['count'] ?? 0;
}

// Featured Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE featured = 1 AND status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['featured_products'] = $row['count'] ?? 0;
}

// Total Orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_orders'] = $row['count'] ?? 0;
}

// Total Customers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_customers'] = $row['count'] ?? 0;
}

// Low Stock Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 5 AND status != 'discontinued'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['low_stock_count'] = $row['count'] ?? 0;
}

// Total Revenue
$result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE order_status = 'completed'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_revenue'] = $row['revenue'] ?? 0;
}

// Recent Products
$recent_products = [];
$result = $conn->query("
    SELECT id, title, author, price, stock_quantity, status, created_at
    FROM products
    ORDER BY created_at DESC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_products[] = $row;
    }
}

// Recent Orders
$recent_orders = [];
$result = $conn->query("
    SELECT id, order_number, customer_id, total_amount, order_status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Low Stock Products
$low_stock = [];
$result = $conn->query("
    SELECT id, title, author, stock_quantity, price
    FROM products
    WHERE stock_quantity <= 5 AND status != 'discontinued'
    ORDER BY stock_quantity ASC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }
}
?>

<!-- Success Message -->
<?php if (!empty($setup_message)): ?>
    <div class="alert alert-success mb-3">
        <i class="bi bi-check-circle"></i>
        <div>
            <strong>Success!</strong><br>
            <?php echo htmlspecialchars($setup_message); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Dashboard Header -->
<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">Welcome back, <?php echo htmlspecialchars($admin_name); ?></h1>
    <p style="color: #7f8c8d; margin: 0;">Here's what's happening with your books & eBooks store</p>
</div>

<!-- Key Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Total Products -->
    <div class="card stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Total Products</p>
                <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                <small style="color: #27ae60;">Active & Listed</small>
            </div>
            <i class="bi bi-book" style="font-size: 2rem; color: #d4a574; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="card stat-card warning">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Featured</p>
                <div class="stat-number" style="color: #f39c12;"><?php echo $stats['featured_products']; ?></div>
                <small style="color: #f39c12;">Highlighted Products</small>
            </div>
            <i class="bi bi-star" style="font-size: 2rem; color: #f39c12; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="card stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Total Orders</p>
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <small style="color: #3498db;">All Time</small>
            </div>
            <i class="bi bi-bag-check" style="font-size: 2rem; color: #3498db; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Total Revenue -->
    <div class="card stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Revenue</p>
                <div class="stat-number">₹<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <small style="color: #27ae60;">Completed Orders</small>
            </div>
            <i class="bi bi-currency-rupee" style="font-size: 2rem; color: #27ae60; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Total Customers -->
    <div class="card stat-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Customers</p>
                <div class="stat-number"><?php echo $stats['total_customers']; ?></div>
                <small style="color: #8e44ad;">Registered Users</small>
            </div>
            <i class="bi bi-people" style="font-size: 2rem; color: #8e44ad; opacity: 0.3;"></i>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="card stat-card danger">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <p style="color: #7f8c8d; font-size: 0.9rem; margin: 0 0 0.5rem 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Low Stock</p>
                <div class="stat-number" style="color: #e74c3c;"><?php echo $stats['low_stock_count']; ?></div>
                <small style="color: #e74c3c;">Products ≤ 5 items</small>
            </div>
            <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #e74c3c; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem;">
    <!-- Recent Products -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="bi bi-plus-circle"></i>
                Recently Added Products
            </h3>
        </div>
        <div class="table-wrapper">
            <?php if (!empty($recent_products)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_products as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($product['title'], 0, 30)); ?></strong>
                                </td>
                                <td style="color: #7f8c8d; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($product['author']); ?>
                                </td>
                                <td>
                                    <strong>₹<?php echo number_format($product['price'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'primary'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #7f8c8d;">
                    <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p>No products added yet</p>
                    <a href="product-add.php" class="btn btn-primary btn-sm" style="display: inline-block; margin-top: 1rem;">Add First Product</a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="products.php" class="btn btn-secondary btn-sm">View All Products</a>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="bi bi-exclamation-triangle"></i>
                Low Stock Alert
            </h3>
        </div>
        <div class="table-wrapper">
            <?php if (!empty($low_stock)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Stock</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($product['title'], 0, 25)); ?></strong>
                                </td>
                                <td style="color: #7f8c8d; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($product['author']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span>
                                </td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: #7f8c8d;">
                    <i class="bi bi-check-circle" style="font-size: 2rem; color: #27ae60; opacity: 0.5;"></i>
                    <p>All products have sufficient stock</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="inventory-management.php" class="btn btn-secondary btn-sm">Manage Inventory</a>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3>
            <i class="bi bi-bag-check"></i>
            Recent Orders
        </h3>
    </div>
    <div class="table-wrapper">
        <?php if (!empty($recent_orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td style="color: #7f8c8d; font-size: 0.9rem;">
                                Customer ID: <?php echo $order['customer_id']; ?>
                            </td>
                            <td>
                                <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $order['order_status'] === 'completed' ? 'success' : 'primary'; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td style="color: #7f8c8d; font-size: 0.9rem;">
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </td>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 2rem; text-align: center; color: #7f8c8d;">
                <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                <p>No orders yet</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <a href="orders.php" class="btn btn-secondary btn-sm">View All Orders</a>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
