<?php
// admin/amazon-seller-dashboard.php - Amazon-like seller dashboard
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Get key metrics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetchArray(SQLITE3_ASSOC)['count'];
$active_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'published'")->fetchArray(SQLITE3_ASSOC)['count'];
$low_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10 AND stock_quantity > 0")->fetchArray(SQLITE3_ASSOC)['count'];
$out_of_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")->fetchArray(SQLITE3_ASSOC)['count'];

// Get recent orders
$recent_orders_stmt = $conn->prepare("SELECT o.*, COALESCE(o.first_name || ' ' || o.last_name, 'Guest') as customer_name FROM orders o ORDER BY o.created_at DESC LIMIT 5");
$recent_orders_result = $recent_orders_stmt->execute();

// Get sales data for chart
$sales_data_stmt = $conn->prepare("SELECT 
    DATE(created_at) as order_date, 
    SUM(total_amount) as daily_sales,
    COUNT(*) as order_count
    FROM orders 
    WHERE created_at >= date('now', '-7 days')
    GROUP BY DATE(created_at) 
    ORDER BY order_date");
$sales_data_result = $sales_data_stmt->execute();

$sales_chart_data = [];
while ($row = $sales_data_result->fetchArray(SQLITE3_ASSOC)) {
    $sales_chart_data[] = $row;
}

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Amazon-like Header -->
    <div class="page-header" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">🏪 Seller Central Dashboard</h1>
                <p class="page-subtitle">Manage your book inventory and sales performance</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="window.print()">
                    <span>🖨️</span> Print Report
                </button>
                <button class="btn-professional btn-primary-professional" onclick="location.href='products-professional.php'">
                    <span>📚</span> Manage Inventory
                </button>
            </div>
        </div>
    </div>

    <!-- Performance Overview Cards -->
    <div class="stats-grid">
        <div class="stat-card-professional" style="border-left-color: #146eb4;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #146eb4 0%, #232f3e 100%);">
                📊
            </div>
            <h2 class="stat-value">₹<?php echo number_format(array_sum(array_column($sales_chart_data, 'daily_sales')), 0); ?></h2>
            <p class="stat-label">7-Day Sales</p>
            <div class="stat-change positive">
                <span>📈</span> Last 7 days
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #232f3e;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($active_products); ?></h2>
            <p class="stat-label">Active Listings</p>
            <div class="stat-change positive">
                <span>✅</span> Published products
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #ff9900;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ff9900 0%, #ffd700 100%);">
                ⚠️
            </div>
            <h2 class="stat-value"><?php echo number_format($low_stock_products); ?></h2>
            <p class="stat-label">Low Stock Items</p>
            <div class="stat-change <?php echo $low_stock_products > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $low_stock_products > 0 ? '⚠️' : '✅'; ?></span> Need attention
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #b12704;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #b12704 0%, #ff6347 100%);">
                🚫
            </div>
            <h2 class="stat-value"><?php echo number_format($out_of_stock_products); ?></h2>
            <p class="stat-label">Out of Stock</p>
            <div class="stat-change <?php echo $out_of_stock_products > 0 ? 'negative' : 'positive'; ?>">
                <span><?php echo $out_of_stock_products > 0 ? '🚫' : '✅'; ?></span> Status
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-6);">
        <!-- Left Column: Charts and Performance -->
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>📈</span> Sales Performance
                </h3>
            </div>
            <div class="card-body-professional">
                <div id="salesChart" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                    <div style="text-align: center; color: var(--gray-500);">
                        <div style="font-size: 3rem; margin-bottom: var(--spacing-2);">📊</div>
                        <p>Sales chart visualization would appear here</p>
                        <p style="font-size: var(--font-size-sm); margin-top: var(--spacing-2);">
                            <?php echo count($sales_chart_data); ?> days of sales data
                        </p>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-4); margin-top: var(--spacing-6);">
                    <div style="text-align: center; padding: var(--spacing-4); background: var(--gray-100); border-radius: var(--border-radius-sm);">
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--primary);">
                            <?php echo array_sum(array_column($sales_chart_data, 'order_count')); ?>
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">Orders</div>
                    </div>
                    <div style="text-align: center; padding: var(--spacing-4); background: var(--gray-100); border-radius: var(--border-radius-sm);">
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--success);">
                            ₹<?php echo number_format(max(array_column($sales_chart_data, 'daily_sales')) ?: 0, 0); ?>
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">Best Day</div>
                    </div>
                    <div style="text-align: center; padding: var(--spacing-4); background: var(--gray-100); border-radius: var(--border-radius-sm);">
                        <div style="font-size: var(--font-size-2xl); font-weight: 700; color: var(--info);">
                            <?php echo count($sales_chart_data) > 0 ? round(array_sum(array_column($sales_chart_data, 'daily_sales')) / count($sales_chart_data)) : 0; ?>%
                        </div>
                        <div style="font-size: var(--font-size-sm); color: var(--gray-600);">Avg. Growth</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Quick Actions and Recent Activity -->
        <div style="display: flex; flex-direction: column; gap: var(--spacing-6);">
            <!-- Quick Actions -->
            <div class="professional-card">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>⚡</span> Quick Actions
                    </h3>
                </div>
                <div class="card-body-professional" style="display: grid; grid-template-columns: 1fr; gap: var(--spacing-3);">
                    <button class="btn-professional btn-primary-professional" onclick="location.href='products-professional.php'">
                        <span>➕</span> Add New Product
                    </button>
                    <button class="btn-professional btn-outline-professional" onclick="location.href='orders.php'">
                        <span>📦</span> Manage Orders
                    </button>
                    <button class="btn-professional btn-info-professional" onclick="location.href='reports-professional.php'">
                        <span>📈</span> View Reports
                    </button>
                    <button class="btn-professional btn-warning-professional" onclick="location.href='settings-professional.php'">
                        <span>⚙️</span> Account Settings
                    </button>
                </div>
            </div>

            <!-- Inventory Alerts -->
            <div class="professional-card">
                <div class="card-header-professional">
                    <h3 class="card-title">
                        <span>⚠️</span> Inventory Alerts
                    </h3>
                </div>
                <div class="card-body-professional">
                    <?php if ($out_of_stock_products > 0): ?>
                        <div style="display: flex; align-items: center; gap: var(--spacing-3); padding: var(--spacing-3); background: #fee2e2; border-radius: var(--border-radius-sm); margin-bottom: var(--spacing-3);">
                            <span style="font-size: 1.5rem;">🚫</span>
                            <div>
                                <div style="font-weight: 600; color: #991b1b;"><?php echo $out_of_stock_products; ?> Out of Stock Items</div>
                                <div style="font-size: var(--font-size-sm); color: #991b1b;">Update inventory levels</div>
                            </div>
                            <button class="btn-professional btn-outline-professional" style="margin-left: auto; padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" onclick="location.href='products-professional.php?stock_status=out_of_stock'">
                                View
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($low_stock_products > 0): ?>
                        <div style="display: flex; align-items: center; gap: var(--spacing-3); padding: var(--spacing-3); background: #fef3c7; border-radius: var(--border-radius-sm); margin-bottom: var(--spacing-3);">
                            <span style="font-size: 1.5rem;">⚠️</span>
                            <div>
                                <div style="font-weight: 600; color: #92400e;"><?php echo $low_stock_products; ?> Low Stock Items</div>
                                <div style="font-size: var(--font-size-sm); color: #92400e;">Running low on inventory</div>
                            </div>
                            <button class="btn-professional btn-outline-professional" style="margin-left: auto; padding: var(--spacing-1) var(--spacing-2); font-size: var(--font-size-xs);" onclick="location.href='products-professional.php?stock_status=low_stock'">
                                View
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($out_of_stock_products == 0 && $low_stock_products == 0): ?>
                        <div style="text-align: center; padding: var(--spacing-6); color: var(--success);">
                            <div style="font-size: 3rem; margin-bottom: var(--spacing-2);">✅</div>
                            <div style="font-weight: 600;">All Good!</div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-1);">No inventory alerts</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="professional-card" style="margin-top: var(--spacing-6);">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>📬</span> Recent Orders
            </h3>
            <button class="btn-professional btn-outline-professional" style="padding: var(--spacing-1) var(--spacing-3); font-size: var(--font-size-sm);" onclick="location.href='orders.php'">
                View All Orders
            </button>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <table class="table-professional">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $order_count = 0;
                    while ($order = $recent_orders_result->fetchArray(SQLITE3_ASSOC)): 
                        $order_count++;
                    ?>
                        <tr class="fade-in">
                            <td>
                                <div style="font-weight: 700; color: var(--primary);">
                                    <?php echo htmlspecialchars($order['order_number'] ?? '#' . str_pad($order['id'], 4, '0', STR_PAD_LEFT)); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div style="font-size: var(--font-size-xs); color: var(--gray-500);"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                            </td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                <div style="font-size: var(--font-size-xs); color: var(--gray-500);"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--success);">
                                    ₹<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo formatStatusBadge($order['order_status']); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($order_count == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: var(--spacing-8);">
                                <div style="color: var(--gray-500);">
                                    <div style="font-size: 3rem; margin-bottom: var(--spacing-3);">📭</div>
                                    <div>No recent orders</div>
                                    <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-1);">Orders will appear here when customers make purchases</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="professional-card" style="margin-top: var(--spacing-6);">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>💡</span> Performance Insights
            </h3>
        </div>
        <div class="card-body-professional">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                <!-- Best Selling Products -->
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>🏆</span> Best Selling Products
                    </h4>
                    <?php
                    $best_sellers_stmt = $conn->prepare("SELECT p.name, p.author, SUM(oi.quantity) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY oi.product_id ORDER BY total_sold DESC LIMIT 3");
                    $best_sellers_result = $best_sellers_stmt->execute();
                    $best_seller_count = 0;
                    ?>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-3);">
                        <?php while ($product = $best_sellers_result->fetchArray(SQLITE3_ASSOC)): 
                            $best_seller_count++;
                        ?>
                            <div style="display: flex; align-items: center; gap: var(--spacing-3); padding: var(--spacing-3); background: var(--gray-100); border-radius: var(--border-radius-sm);">
                                <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--font-size-xs);">
                                    <?php echo $best_seller_count; ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: var(--font-size-sm);"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div style="font-size: var(--font-size-xs); color: var(--gray-600);"><?php echo htmlspecialchars($product['author']); ?></div>
                                </div>
                                <div style="font-weight: 700; color: var(--primary);"><?php echo $product['total_sold']; ?> sold</div>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($best_seller_count == 0): ?>
                            <div style="text-align: center; padding: var(--spacing-6); color: var(--gray-500);">
                                <div style="font-size: 2rem; margin-bottom: var(--spacing-2);">📚</div>
                                <div>No sales data available</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seller Tips -->
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>🎓</span> Seller Tips
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <div style="padding: var(--spacing-4); background: #dbeafe; border-radius: var(--border-radius-sm); border-left: 4px solid var(--info);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📸</span> Optimize Product Images
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Use high-quality images with multiple angles to increase conversion rates by up to 35%.
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #d1fae5; border-radius: var(--border-radius-sm); border-left: 4px solid var(--success);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>🏷️</span> Competitive Pricing
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Regularly review competitor pricing and adjust yours to stay competitive in the marketplace.
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #fef3c7; border-radius: var(--border-radius-sm); border-left: 4px solid var(--warning);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📦</span> Inventory Management
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Keep 20% more inventory than your average monthly sales to avoid stockouts during peak periods.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Professional enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on load
    const statCards = document.querySelectorAll('.stat-card-professional');
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Simulate chart loading
    const chartContainer = document.getElementById('salesChart');
    if (chartContainer) {
        setTimeout(() => {
            chartContainer.innerHTML = `
                <div style="width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                    <div style="font-size: 3rem; margin-bottom: var(--spacing-4);">📊</div>
                    <div style="text-align: center;">
                        <h3 style="margin: 0 0 var(--spacing-2) 0;">Sales Performance</h3>
                        <p style="color: var(--gray-600); margin: 0;">Last 7 days: ₹<?php echo number_format(array_sum(array_column($sales_chart_data, 'daily_sales')), 0); ?></p>
                    </div>
                    <div style="width: 90%; height: 200px; margin-top: var(--spacing-4); display: flex; align-items: flex-end; justify-content: space-around; gap: var(--spacing-2);">
                        <?php foreach ($sales_chart_data as $data): ?>
                            <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                                <div style="width: 100%; background: linear-gradient(to top, var(--primary), var(--secondary)); border-radius: 4px 4px 0 0; height: <?php echo max(10, ($data['daily_sales'] / max(array_column($sales_chart_data, 'daily_sales')) * 150)); ?>px;"></div>
                                <div style="font-size: var(--font-size-xs); margin-top: var(--spacing-2); color: var(--gray-600);">
                                    <?php echo date('M j', strtotime($data['order_date'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            `;
        }, 1000);
    }
});
</script>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>