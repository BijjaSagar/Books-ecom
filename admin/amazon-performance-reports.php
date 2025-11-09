<?php
// admin/amazon-performance-reports.php - Amazon-like performance reports
include '../includes/admin_header.php';
include 'includes/professional-components.php';

// Get date range filters
$date_range = $_GET['date_range'] ?? '7_days';
$custom_date_from = $_GET['date_from'] ?? '';
$custom_date_to = $_GET['date_to'] ?? '';

// Calculate date range
switch ($date_range) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case '7_days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case '30_days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case '90_days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        $end_date = date('Y-m-d');
        break;
    case 'custom':
        $start_date = $custom_date_from ?: date('Y-m-d', strtotime('-7 days'));
        $end_date = $custom_date_to ?: date('Y-m-d');
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
}

// Get sales data
$sales_stmt = $conn->prepare("SELECT 
    DATE(created_at) as order_date, 
    SUM(total_amount) as daily_sales,
    COUNT(*) as order_count
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) 
    ORDER BY order_date");
$sales_stmt->bindValue(1, $start_date, SQLITE3_TEXT);
$sales_stmt->bindValue(2, $end_date, SQLITE3_TEXT);
$sales_result = $sales_stmt->execute();

$sales_data = [];
$total_sales = 0;
$total_orders = 0;
while ($row = $sales_result->fetchArray(SQLITE3_ASSOC)) {
    $sales_data[] = $row;
    $total_sales += $row['daily_sales'];
    $total_orders += $row['order_count'];
}

// Get top selling products
$top_products_stmt = $conn->prepare("SELECT 
    p.name, 
    p.author, 
    SUM(oi.quantity) as total_sold, 
    SUM(oi.total) as total_revenue
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_id 
    ORDER BY total_sold DESC 
    LIMIT 10");
$top_products_stmt->bindValue(1, $start_date, SQLITE3_TEXT);
$top_products_stmt->bindValue(2, $end_date, SQLITE3_TEXT);
$top_products_result = $top_products_stmt->execute();

// Get order status distribution
$status_distribution_stmt = $conn->prepare("SELECT 
    order_status, 
    COUNT(*) as count 
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY order_status");
$status_distribution_stmt->bindValue(1, $start_date, SQLITE3_TEXT);
$status_distribution_stmt->bindValue(2, $end_date, SQLITE3_TEXT);
$status_distribution_result = $status_distribution_stmt->execute();

$status_data = [];
while ($row = $status_distribution_result->fetchArray(SQLITE3_ASSOC)) {
    $status_data[$row['order_status']] = $row['count'];
}

// Get customer data
$new_customers_stmt = $conn->prepare("SELECT COUNT(*) as new_customers FROM users WHERE DATE(created_at) BETWEEN ? AND ? AND role = 'customer'");
$new_customers_stmt->bindValue(1, $start_date, SQLITE3_TEXT);
$new_customers_stmt->bindValue(2, $end_date, SQLITE3_TEXT);
$new_customers_result = $new_customers_stmt->execute();
$new_customers = $new_customers_result->fetchArray(SQLITE3_ASSOC)['new_customers'];

// Calculate key metrics
$avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
$best_day_sales = !empty($sales_data) ? max(array_column($sales_data, 'daily_sales')) : 0;

injectProfessionalCSS();
?>

<div class="admin-container">
    <!-- Amazon-like Header -->
    <div class="page-header" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">📈 Performance Reports</h1>
                <p class="page-subtitle">Analyze your business performance and sales trends</p>
            </div>
            <div style="display: flex; gap: var(--spacing-3); flex-wrap: wrap;">
                <button class="btn-professional btn-outline-professional" onclick="exportReport()">
                    <span>📥</span> Export Report
                </button>
                <button class="btn-professional btn-primary-professional" onclick="printReport()">
                    <span>🖨️</span> Print Report
                </button>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filters-professional" style="margin-bottom: var(--spacing-6);">
        <form method="GET" class="filters-row">
            <div class="form-group-professional">
                <label class="form-label-professional">📅 Date Range</label>
                <select name="date_range" class="form-control-professional form-select-professional" onchange="handleDateRangeChange(this)">
                    <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="7_days" <?php echo $date_range === '7_days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30_days" <?php echo $date_range === '30_days' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90_days" <?php echo $date_range === '90_days' ? 'selected' : ''; ?>>Last 90 Days</option>
                    <option value="custom" <?php echo $date_range === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            
            <div class="form-group-professional" id="customDateFields" style="display: <?php echo $date_range === 'custom' ? 'grid' : 'none'; ?>; grid-template-columns: 1fr 1fr; gap: var(--spacing-4);">
                <div>
                    <label class="form-label-professional">📅 From Date</label>
                    <input type="date" class="form-control-professional" name="date_from" value="<?php echo htmlspecialchars($custom_date_from); ?>">
                </div>
                <div>
                    <label class="form-label-professional">📅 To Date</label>
                    <input type="date" class="form-control-professional" name="date_to" value="<?php echo htmlspecialchars($custom_date_to); ?>">
                </div>
            </div>
            
            <div class="form-group-professional">
                <label class="form-label-professional">&nbsp;</label>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="submit" class="btn-professional btn-primary-professional">
                        <span>📊</span> Generate Report
                    </button>
                    <a href="amazon-performance-reports.php" class="btn-professional btn-outline-professional">
                        <span>🔄</span> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Key Metrics Overview -->
    <div class="stats-grid">
        <div class="stat-card-professional" style="border-left-color: #146eb4;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #146eb4 0%, #232f3e 100%);">
                💰
            </div>
            <h2 class="stat-value">₹<?php echo number_format($total_sales, 0); ?></h2>
            <p class="stat-label">Total Sales</p>
            <div class="stat-change positive">
                <span>📈</span> In selected period
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #232f3e;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #232f3e 0%, #146eb4 100%);">
                📦
            </div>
            <h2 class="stat-value"><?php echo number_format($total_orders); ?></h2>
            <p class="stat-label">Total Orders</p>
            <div class="stat-change positive">
                <span>📬</span> Order volume
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #28a745;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                💵
            </div>
            <h2 class="stat-value">₹<?php echo number_format($avg_order_value, 0); ?></h2>
            <p class="stat-label">Avg. Order Value</p>
            <div class="stat-change positive">
                <span>💰</span> Per order
            </div>
        </div>
        
        <div class="stat-card-professional" style="border-left-color: #ffc107;">
            <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);">
                👥
            </div>
            <h2 class="stat-value"><?php echo number_format($new_customers); ?></h2>
            <p class="stat-label">New Customers</p>
            <div class="stat-change positive">
                <span>👤</span> Acquired
            </div>
        </div>
    </div>

    <!-- Charts and Data Visualization -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-6); margin-bottom: var(--spacing-6);">
        <!-- Sales Chart -->
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>📊</span> Sales Performance
                    <span style="font-size: var(--font-size-sm); color: var(--gray-600); margin-left: var(--spacing-2);">
                        <?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>
                    </span>
                </h3>
            </div>
            <div class="card-body-professional">
                <div id="salesChart" style="height: 300px; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($sales_data)): ?>
                        <div style="width: 100%; height: 100%;">
                            <div style="display: flex; align-items: flex-end; justify-content: space-around; height: 250px; gap: var(--spacing-1); padding: 0 var(--spacing-4);">
                                <?php foreach ($sales_data as $data): ?>
                                    <div style="display: flex; flex-direction: column; align-items: center; flex: 1; max-width: 40px;">
                                        <div style="width: 100%; background: linear-gradient(to top, var(--primary), var(--secondary)); border-radius: 4px 4px 0 0; height: <?php echo max(5, ($data['daily_sales'] / $best_day_sales * 200)); ?>px;"></div>
                                        <div style="font-size: var(--font-size-xs); margin-top: var(--spacing-2); color: var(--gray-600); transform: rotate(-45deg); transform-origin: center; white-space: nowrap;">
                                            <?php echo date('M j', strtotime($data['order_date'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="text-align: center; margin-top: var(--spacing-4); color: var(--gray-600);">
                                Daily Sales (₹)
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; color: var(--gray-500);">
                            <div style="font-size: 3rem; margin-bottom: var(--spacing-2);">📊</div>
                            <p>No sales data available for the selected period</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="professional-card">
            <div class="card-header-professional">
                <h3 class="card-title">
                    <span>📋</span> Order Status Distribution
                </h3>
            </div>
            <div class="card-body-professional">
                <?php if (!empty($status_data)): ?>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <?php 
                        $status_labels = [
                            'pending' => ['Pending', '#ffc107'],
                            'processing' => ['Processing', '#17a2b8'],
                            'shipped' => ['Shipped', '#6f42c1'],
                            'delivered' => ['Delivered', '#28a745'],
                            'cancelled' => ['Cancelled', '#dc3545']
                        ];
                        $total_status_count = array_sum($status_data);
                        ?>
                        <?php foreach ($status_data as $status => $count): 
                            $percentage = $total_status_count > 0 ? ($count / $total_status_count) * 100 : 0;
                            $label = $status_labels[$status][0] ?? ucfirst($status);
                            $color = $status_labels[$status][1] ?? '#6c757d';
                        ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-1);">
                                    <div style="display: flex; align-items: center; gap: var(--spacing-2);">
                                        <div style="width: 12px; height: 12px; background: <?php echo $color; ?>; border-radius: 50%;"></div>
                                        <span><?php echo $label; ?></span>
                                    </div>
                                    <div style="font-weight: 600;"><?php echo $count; ?> (<?php echo round($percentage, 1); ?>%)</div>
                                </div>
                                <div style="height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: var(--gray-500); padding: var(--spacing-8) 0;">
                        <div style="font-size: 3rem; margin-bottom: var(--spacing-2);">📋</div>
                        <p>No order status data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="professional-card" style="margin-bottom: var(--spacing-6);">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>🏆</span> Top Selling Products
            </h3>
        </div>
        <div class="card-body-professional" style="padding: 0;">
            <?php if ($top_products_result && $top_products_result->numRows() > 0): ?>
                <table class="table-professional">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Product</th>
                            <th>Author</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while ($product = $top_products_result->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr class="fade-in">
                                <td>
                                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--font-size-sm);">
                                        <?php echo $rank; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($product['name']); ?></div>
                                </td>
                                <td>
                                    <div style="color: var(--gray-600);"><?php echo htmlspecialchars($product['author']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);"><?php echo number_format($product['total_sold']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--success);">₹<?php echo number_format($product['total_revenue'], 0); ?></div>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                        endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: var(--spacing-12); color: var(--gray-500);">
                    <div style="font-size: 4rem; margin-bottom: var(--spacing-4); opacity: 0.5;">📚</div>
                    <h4 style="color: var(--gray-600); margin: var(--spacing-2) 0;">No sales data available</h4>
                    <p style="margin: 0;">Top selling products will appear here once customers start purchasing</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Business Insights -->
    <div class="professional-card">
        <div class="card-header-professional">
            <h3 class="card-title">
                <span>💡</span> Business Insights
            </h3>
        </div>
        <div class="card-body-professional">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-6);">
                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>🎯</span> Sales Trends
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <div style="padding: var(--spacing-4); background: #dbeafe; border-radius: var(--border-radius-sm); border-left: 4px solid var(--info);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📈</span> Peak Sales Days
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                <?php 
                                if (!empty($sales_data)) {
                                    $peak_day = array_reduce($sales_data, function($carry, $item) {
                                        return ($carry === null || $item['daily_sales'] > $carry['daily_sales']) ? $item : $carry;
                                    });
                                    echo "Your best sales day was " . date('l, M j', strtotime($peak_day['order_date'])) . " with ₹" . number_format($peak_day['daily_sales'], 0) . " in sales.";
                                } else {
                                    echo "Analyze your sales patterns to identify peak days for targeted promotions.";
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #d1fae5; border-radius: var(--border-radius-sm); border-left: 4px solid var(--success);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>💰</span> Average Order Value
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                Your average order value is ₹<?php echo number_format($avg_order_value, 0); ?>. Consider bundling products or offering free shipping thresholds to increase this metric.
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 style="margin: 0 0 var(--spacing-4) 0; display: flex; align-items: center; gap: var(--spacing-2);">
                        <span>🚀</span> Growth Opportunities
                    </h4>
                    <div style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <div style="padding: var(--spacing-4); background: #fef3c7; border-radius: var(--border-radius-sm); border-left: 4px solid var(--warning);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📢</span> Marketing Recommendations
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                <?php 
                                if ($new_customers > 0) {
                                    echo "You acquired {$new_customers} new customers in this period. Consider implementing a referral program to leverage word-of-mouth marketing.";
                                } else {
                                    echo "Focus on customer acquisition strategies such as social media marketing and email campaigns to attract new customers.";
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div style="padding: var(--spacing-4); background: #e0e7ff; border-radius: var(--border-radius-sm); border-left: 4px solid var(--primary);">
                            <div style="font-weight: 600; display: flex; align-items: center; gap: var(--spacing-2);">
                                <span>📦</span> Inventory Optimization
                            </div>
                            <div style="font-size: var(--font-size-sm); margin-top: var(--spacing-2); color: var(--gray-700);">
                                <?php 
                                if ($top_products_result && $top_products_result->numRows() > 0) {
                                    $top_products_result->reset();
                                    $top_product = $top_products_result->fetchArray(SQLITE3_ASSOC);
                                    echo "Your best-selling product is '{$top_product['name']}'. Ensure adequate stock levels and consider creating variants or related products.";
                                } else {
                                    echo "Regularly review inventory levels and sales trends to optimize stock and avoid overstock or stockouts.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Professional JavaScript -->
<script>
function handleDateRangeChange(selectElement) {
    const customFields = document.getElementById('customDateFields');
    if (selectElement.value === 'custom') {
        customFields.style.display = 'grid';
    } else {
        customFields.style.display = 'none';
    }
}

function exportReport() {
    alert('Export functionality would be implemented here');
}

function printReport() {
    window.print();
}

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
    
    // Animate table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>

<style>
@media print {
    .page-header, .filters-professional, .btn-professional {
        display: none !important;
    }
    
    .admin-container {
        padding: 0 !important;
    }
    
    .professional-card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
}
</style>

<?php renderProfessionalJavaScript(); ?>
<?php include '../includes/admin_footer.php'; ?>