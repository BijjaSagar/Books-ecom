<?php
/**
 * admin/payment-analytics.php - Payment Gateway Analytics & Integration
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/PaymentGatewayIntegration.php';

$paymentIntegration = new PaymentGatewayIntegration($conn);

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get payment data
$summary = $paymentIntegration->getPaymentGatewaySummary(30);
$analytics = $paymentIntegration->getPaymentAnalytics($startDate, $endDate);
$failedPayments = $paymentIntegration->getFailedPayments(15);
$paymentMethods = $paymentIntegration->getPaymentMethodBreakdown($startDate, $endDate);

// Sync data
if (isset($_POST['action']) && $_POST['action'] === 'sync') {
    $gateway = $_POST['gateway'] ?? '';
    if ($gateway === 'paypal') {
        $syncResult = $paymentIntegration->syncPayPalTransactions();
    } elseif ($gateway === 'razorpay') {
        $syncResult = $paymentIntegration->syncRazorpayTransactions();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Analytics - Bookory Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        .metric-box { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff; }
        .metric-value { font-size: 28px; font-weight: bold; color: #007bff; }
        .metric-label { color: #666; font-size: 13px; margin-top: 5px; }
        .gateway-badge { padding: 4px 12px; border-radius: 20px; font-weight: bold; }
        .paypal-badge { background: #003087; color: white; }
        .razorpay-badge { background: #5B21B6; color: white; }
        .chart-container { position: relative; height: 300px; margin-bottom: 20px; }
        .sidebar { background-color: #2c3e50; min-height: 100vh; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { background-color: #34495e; }
        .sidebar a.active { background-color: #007bff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="text-white"><i class="bi bi-bar-chart"></i> Bookory Admin</h5>
                </div>
                <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="sales-dashboard.php"><i class="bi bi-graph-up"></i> Sales</a>
                <a href="inventory-management.php"><i class="bi bi-box"></i> Inventory</a>
                <a href="payment-analytics.php" class="active"><i class="bi bi-credit-card"></i> Payments</a>
                <a href="financial-dashboard.php"><i class="bi bi-cash-flow"></i> Financial</a>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div style="background-color: #34495e; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2><i class="bi bi-credit-card"></i> Payment Analytics</h2>
                    <p class="mb-0">PayPal & Razorpay integration metrics</p>
                </div>

                <!-- Summary Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-box">
                            <div class="metric-value"><?php echo $summary['total_transactions'] ?? 0; ?></div>
                            <div class="metric-label">Total Transactions</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #28a745;">
                            <div class="metric-value" style="color: #28a745;">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #ffc107;">
                            <div class="metric-value" style="color: #ffc107;"><?php echo round($summary['success_rate'] ?? 0, 1); ?>%</div>
                            <div class="metric-label">Success Rate</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box" style="border-left-color: #dc3545;">
                            <div class="metric-value" style="color: #dc3545;"><?php echo $summary['failed_count'] ?? 0; ?></div>
                            <div class="metric-label">Failed</div>
                        </div>
                    </div>
                </div>

                <!-- Sync Controls -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-arrow-repeat"></i> Sync Payment Data</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="sync">
                                <input type="hidden" name="gateway" value="paypal">
                                <button type="submit" class="btn btn-sm" style="background: #003087; color: white;">
                                    <i class="bi bi-arrow-repeat"></i> Sync PayPal
                                </button>
                            </form>
                            <small class="text-muted d-block mt-2">Fetch latest PayPal transactions</small>
                        </div>
                        <div class="col-md-6">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="sync">
                                <input type="hidden" name="gateway" value="razorpay">
                                <button type="submit" class="btn btn-sm" style="background: #5B21B6; color: white;">
                                    <i class="bi bi-arrow-repeat"></i> Sync Razorpay
                                </button>
                            </form>
                            <small class="text-muted d-block mt-2">Fetch latest Razorpay transactions</small>
                        </div>
                    </div>
                    <?php if (isset($syncResult)): ?>
                    <div class="alert alert-<?php echo $syncResult['success'] ? 'success' : 'danger'; ?> mt-3 mb-0">
                        <?php echo $syncResult['message'] ?? $syncResult['error']; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Gateway Comparison -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-pie-chart"></i> Payment Gateway Performance</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Gateway</th>
                                    <th>Transactions</th>
                                    <th>Revenue</th>
                                    <th>Successful</th>
                                    <th>Failed</th>
                                    <th>Refunded</th>
                                    <th>Fees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analytics as $gateway): ?>
                                <tr>
                                    <td>
                                        <span class="gateway-badge <?php echo $gateway['payment_gateway'] === 'paypal' ? 'paypal-badge' : 'razorpay-badge'; ?>">
                                            <?php echo ucfirst($gateway['payment_gateway']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $gateway['transaction_count']; ?></td>
                                    <td>$<?php echo number_format($gateway['total_amount'], 2); ?></td>
                                    <td><span class="badge bg-success"><?php echo $gateway['successful']; ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo $gateway['failed']; ?></span></td>
                                    <td><span class="badge bg-warning"><?php echo $gateway['refunded']; ?></span></td>
                                    <td>$<?php echo number_format($gateway['total_fees'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="dashboard-card">
                    <h5><i class="bi bi-wallet2"></i> Payment Methods Breakdown</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalAmount = array_sum(array_column($paymentMethods, 'total_amount'));
                                foreach ($paymentMethods as $method):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                    <td><?php echo $method['count']; ?></td>
                                    <td>$<?php echo number_format($method['total_amount'], 2); ?></td>
                                    <td><?php echo round(($method['total_amount'] / $totalAmount) * 100, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Failed Payments -->
                <?php if (!empty($failedPayments)): ?>
                <div class="dashboard-card">
                    <h5 class="text-danger"><i class="bi bi-exclamation-circle"></i> Failed Payments</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Gateway</th>
                                    <th>Amount</th>
                                    <th>Error</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failedPayments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['order_id'] ?? 'N/A'; ?></td>
                                    <td><span class="badge <?php echo $payment['payment_gateway'] === 'paypal' ? 'bg-primary' : 'bg-warning'; ?>"><?php echo ucfirst($payment['payment_gateway'] ?? 'N/A'); ?></span></td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><small><?php echo htmlspecialchars(substr($payment['response_message'] ?? 'Unknown error', 0, 50)); ?></small></td>
                                    <td><?php echo date('M d, H:i', strtotime($payment['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Date Range Filter -->
                <div class="dashboard-card">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Integration Info -->
                <div class="dashboard-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5><i class="bi bi-info-circle"></i> Payment Gateway Setup</h5>
                    <p class="mb-2"><strong>Environment Variables Required:</strong></p>
                    <ul style="font-size: 13px;">
                        <li>PAYPAL_CLIENT_ID</li>
                        <li>PAYPAL_SECRET</li>
                        <li>PAYPAL_SIGNATURE</li>
                        <li>RAZORPAY_KEY_ID</li>
                        <li>RAZORPAY_KEY_SECRET</li>
                    </ul>
                    <p style="margin-top: 10px; margin-bottom: 0; font-size: 13px;">
                        <i class="bi bi-check-circle"></i> Both gateways are configured and ready to use
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
