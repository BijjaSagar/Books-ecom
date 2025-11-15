<?php
/**
 * Customer Dashboard
 * Shows customer order history, account information, and order tracking
 */

session_start();

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /login.php');
    exit;
}

require_once '../includes/config.php';

// Get customer information
$customer_id = $_SESSION['user_id'];
$customer = $conn->prepare("SELECT * FROM users WHERE id = ?");
$customer->bind_param('i', $customer_id);
$customer->execute();
$customer_data = $customer->get_result()->fetch_assoc();
$customer->close();

// Get order statistics
$stats_query = "SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(total_amount) as total_spent
                FROM orders
                WHERE user_id = ?";

$stats = $conn->prepare($stats_query);
$stats->bind_param('i', $customer_id);
$stats->execute();
$stats_data = $stats->get_result()->fetch_assoc();
$stats->close();

// Get recent orders
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$orders = $conn->prepare($orders_query);
$orders->bind_param('i', $customer_id);
$orders->execute();
$orders_result = $orders->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Bookstore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
        }

        body {
            background: #f3f4f6;
            color: #374151;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 16px;
        }

        .order-number {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        .order-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #e0e7ff;
            color: #5b21b6;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .btn-view-order {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view-order:hover {
            background: var(--primary-dark);
            color: white;
            text-decoration: none;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--primary);
        }

        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #374151;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 1.75rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1>👋 Welcome, <?php echo htmlspecialchars($customer_data['name']); ?></h1>
            <p>Manage your orders and account information</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo $stats_data['total_orders'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card warning">
                    <div class="stat-label">Pending Orders</div>
                    <div class="stat-value"><?php echo $stats_data['pending_orders'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card success">
                    <div class="stat-label">Delivered Orders</div>
                    <div class="stat-value"><?php echo $stats_data['delivered_orders'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card success">
                    <div class="stat-label">Total Spent</div>
                    <div class="stat-value">₹<?php echo number_format($stats_data['total_spent'] ?? 0, 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist" style="border-bottom: 2px solid var(--primary);">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#orders">
                    <i class="bi bi-bag"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#profile">
                    <i class="bi bi-person"></i> Account
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Orders Tab -->
            <div id="orders" class="tab-pane fade show active">
                <h2 class="section-title">📦 Your Orders</h2>

                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                    <div style="color: #6b7280; font-size: 0.9rem;">
                                        <?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <div class="detail-label">Order Total</div>
                                    <div class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Delivery Address</div>
                                    <div class="detail-value" style="font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Payment Method</div>
                                    <div class="detail-value" style="font-size: 0.95rem;">
                                        <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <a href="order-details.php?order_id=<?php echo $order['id']; ?>" class="btn-view-order">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders. Start shopping now!</p>
                        <a href="/shop.php" class="btn-view-order" style="margin-top: 20px;">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-pane fade">
                <h2 class="section-title">👤 My Account</h2>

                <div class="row">
                    <div class="col-md-6">
                        <div class="profile-section">
                            <h3 style="margin-bottom: 20px; color: var(--primary);">Personal Information</h3>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($customer_data['name']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($customer_data['email']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" value="<?php echo htmlspecialchars($customer_data['phone'] ?? ''); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Member Since</label>
                                <input type="text" value="<?php echo date('M j, Y', strtotime($customer_data['created_at'])); ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-section">
                            <h3 style="margin-bottom: 20px; color: var(--primary);">Account Statistics</h3>
                            <div class="form-group">
                                <label>Account Status</label>
                                <input type="text" value="<?php echo ucfirst($customer_data['status'] ?? 'active'); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Purchases</label>
                                <input type="text" value="<?php echo $stats_data['total_orders'] ?? 0; ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Total Spent</label>
                                <input type="text" value="₹<?php echo number_format($stats_data['total_spent'] ?? 0, 2); ?>" readonly>
                            </div>
                            <a href="edit-profile.php" class="btn-view-order" style="margin-top: 20px;">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$orders->close();
$conn->close();
?>
