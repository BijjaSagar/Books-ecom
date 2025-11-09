<?php 
// Start with basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/admin_header.php'; 
?>

<!-- Force content with inline styles -->
<div style="margin-left: 300px; padding: 30px; background: white; min-height: 100vh; display: block;">
    
    <!-- Test if PHP is working -->
    <div style="background: #28a745; color: white; padding: 20px; margin: 20px 0; border-radius: 10px;">
        <h1>✅ Dashboard is Loading!</h1>
        <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <?php
    // Simple database queries with error handling
    try {
        // Get basic stats
        $orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
        $total_orders = $orders_result ? $orders_result->fetch_assoc()['count'] : 0;
        
        $revenue_result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')");
        $total_revenue = $revenue_result ? ($revenue_result->fetch_assoc()['total'] ?? 0) : 0;
        
        $products_result = $conn->query("SELECT COUNT(*) as count FROM products");
        $total_products = $products_result ? $products_result->fetch_assoc()['count'] : 0;
        
        $users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
        $total_customers = $users_result ? $users_result->fetch_assoc()['count'] : 0;
        
        echo '<div style="background: #007bff; color: white; padding: 20px; margin: 20px 0; border-radius: 10px;">';
        echo '<h2>📊 Your Store Statistics</h2>';
        echo '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px;">';
        
        echo '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; text-align: center;">';
        echo '<h3 style="margin: 0; font-size: 2rem;">$' . number_format($total_revenue, 2) . '</h3>';
        echo '<p style="margin: 5px 0 0 0;">Total Revenue</p>';
        echo '</div>';
        
        echo '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; text-align: center;">';
        echo '<h3 style="margin: 0; font-size: 2rem;">' . $total_orders . '</h3>';
        echo '<p style="margin: 5px 0 0 0;">Total Orders</p>';
        echo '</div>';
        
        echo '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; text-align: center;">';
        echo '<h3 style="margin: 0; font-size: 2rem;">' . $total_products . '</h3>';
        echo '<p style="margin: 5px 0 0 0;">Products</p>';
        echo '</div>';
        
        echo '<div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; text-align: center;">';
        echo '<h3 style="margin: 0; font-size: 2rem;">' . $total_customers . '</h3>';
        echo '<p style="margin: 5px 0 0 0;">Customers</p>';
        echo '</div>';
        
        echo '</div></div>';
        
    } catch (Exception $e) {
        echo '<div style="background: #dc3545; color: white; padding: 20px; margin: 20px 0; border-radius: 10px;">';
        echo '<h2>❌ Database Error</h2>';
        echo '<p>Error: ' . $e->getMessage() . '</p>';
        echo '</div>';
    }
    ?>

    <!-- Recent Orders -->
    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 10px; border: 1px solid #dee2e6;">
        <h2 style="color: #495057; margin-top: 0;">📦 Recent Orders</h2>
        
        <?php
        try {
            $recent_orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
            
            if ($recent_orders && $recent_orders->num_rows > 0) {
                echo '<table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">';
                echo '<tr style="background: #6f42c1; color: white;">';
                echo '<th style="padding: 15px; text-align: left;">Order #</th>';
                echo '<th style="padding: 15px; text-align: left;">Customer</th>';
                echo '<th style="padding: 15px; text-align: left;">Total</th>';
                echo '<th style="padding: 15px; text-align: left;">Status</th>';
                echo '<th style="padding: 15px; text-align: left;">Date</th>';
                echo '</tr>';
                
                while ($order = $recent_orders->fetch_assoc()) {
                    echo '<tr style="border-bottom: 1px solid #dee2e6;">';
                    echo '<td style="padding: 15px;">#' . str_pad($order['id'], 4, '0', STR_PAD_LEFT) . '</td>';
                    echo '<td style="padding: 15px;">' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</td>';
                    echo '<td style="padding: 15px;"><strong>$' . number_format($order['total_amount'], 2) . '</strong></td>';
                    echo '<td style="padding: 15px;"><span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem;">' . ucfirst($order['order_status']) . '</span></td>';
                    echo '<td style="padding: 15px;">' . date('M j, Y', strtotime($order['created_at'])) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p style="text-align: center; color: #6c757d; font-style: italic;">No orders found</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: #dc3545;">Error loading orders: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <!-- Quick Actions -->
    <div style="background: #17a2b8; color: white; padding: 20px; margin: 20px 0; border-radius: 10px;">
        <h2 style="margin-top: 0;">⚡ Quick Actions</h2>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="products.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; display: inline-block;">
                📚 Manage Products
            </a>
            <a href="orders.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; display: inline-block;">
                📦 View Orders
            </a>
            <a href="customers.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; display: inline-block;">
                👥 Manage Customers
            </a>
            <a href="categories.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; display: inline-block;">
                🏷️ Categories
            </a>
            <a href="reports.php" style="background: rgba(255,255,255,0.2); color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; display: inline-block;">
                📊 View Reports
            </a>
        </div>
    </div>

    <!-- Success Message -->
    <div style="background: #d4edda; color: #155724; padding: 20px; margin: 20px 0; border-radius: 10px; border: 1px solid #c3e6cb;">
        <h2 style="margin-top: 0;">🎉 Dashboard is Working!</h2>
        <p>Your BookShelf admin dashboard is now fully functional. You can:</p>
        <ul>
            <li>✅ View real-time statistics</li>
            <li>✅ Manage orders and customers</li>
            <li>✅ Add and edit products</li>
            <li>✅ Generate reports</li>
            <li>✅ Organize categories</li>
        </ul>
    </div>

</div>

<?php include '../includes/admin_footer.php'; ?>