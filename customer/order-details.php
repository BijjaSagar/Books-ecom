<?php
/**
 * Customer Order Details Page
 * Display complete order information with timeline and tracking
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/config.php';
require_once '../includes/OrderManager.php';

$order_id = intval($_GET['id'] ?? 0);

if ($order_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Get order details - verify ownership
$order_stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
");
$order_stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();
$order_stmt->close();

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Get order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.title, p.author 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Get order timeline
$order_manager = new OrderManager($conn);
$timeline = $order_manager->getOrderTimeline($order_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order['id']; ?> - Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-dark: #1e3a8a;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border-color: #e5e7eb;
            --text-primary: #374151;
            --text-secondary: #6b7280;
        }

        body {
            background: #f3f4f6;
        }

        .page-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 32px 0;
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .page-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .card-header {
            background: #f9fafb;
            border-bottom: 2px solid var(--border-color);
            padding: 20px;
            font-weight: 600;
            color: var(--primary);
        }

        .card-body {
            padding: 24px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
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

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .info-box {
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .item-table th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            font-size: 0.9rem;
        }

        .item-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .item-table tr:hover {
            background: #f9fafb;
        }

        .item-name {
            font-weight: 600;
        }

        .item-author {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .total-row {
            background: #1e40af;
            color: white;
            font-weight: 700;
            padding: 12px;
            text-align: right;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }

        .timeline-item {
            margin-bottom: 24px;
            position: relative;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -40px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--primary);
            border: 4px solid white;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .timeline-item.completed:before {
            background: var(--success);
            box-shadow: 0 0 0 2px var(--success);
        }

        .timeline-item:not(:last-child):after {
            content: '';
            position: absolute;
            left: -33px;
            top: 25px;
            height: calc(100% + 24px);
            width: 2px;
            background: var(--border-color);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .timeline-status {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .timeline-note {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .delivery-info-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--success);
        }

        .delivery-info-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .delivery-info-value {
            color: var(--text-primary);
            line-height: 1.6;
        }

        .price-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }

        .price-item {
            display: flex;
            justify-content: space-between;
        }

        .price-label {
            color: var(--text-secondary);
        }

        .price-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .action-button {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            background: var(--primary-dark);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .price-summary {
                grid-template-columns: 1fr;
            }

            .item-table {
                font-size: 0.9rem;
            }

            .item-table th,
            .item-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <a href="dashboard.php" class="back-link">← Back to Orders</a>
            <h1>Order #<?php echo $order['id']; ?></h1>
            <p>Order placed on <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Order Status -->
        <div class="card">
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start;">
                    <div>
                        <h5 style="margin-bottom: 12px; color: var(--primary);">Current Status</h5>
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 4px;">Order Number</div>
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--text-primary);">ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div class="card">
            <div class="card-header">📋 Order Information</div>
            <div class="card-body">
                <div class="order-info-grid">
                    <div class="info-box">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">₹<?php echo number_format($order['total_amount'], 0); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo ucfirst($order['payment_method']); ?></div>
                    </div>
                    <div class="info-box">
                        <div class="info-label">Number of Items</div>
                        <div class="info-value"><?php echo count($items); ?> Product<?php echo count($items) !== 1 ? 's' : ''; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-header">📦 Order Items</div>
            <div class="card-body">
                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="width: 80px;">Quantity</th>
                            <th style="text-align: right; width: 100px;">Price</th>
                            <th style="text-align: right; width: 100px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="item-name"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="item-author">by <?php echo htmlspecialchars($item['author']); ?></div>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td style="text-align: right;">₹<?php echo number_format($item['price'], 0); ?></td>
                                <td style="text-align: right; font-weight: 600;">₹<?php echo number_format($item['quantity'] * $item['price'], 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total-row">
                    Total: ₹<?php echo number_format($order['total_amount'], 0); ?>
                </div>
            </div>
        </div>

        <!-- Delivery Information -->
        <div class="card">
            <div class="card-header">🚚 Delivery Information</div>
            <div class="card-body">
                <div class="delivery-info-box">
                    <div class="delivery-info-label">Delivery Address</div>
                    <div class="delivery-info-value">
                        <?php echo htmlspecialchars($order['delivery_address']); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['pincode']); ?>
                    </div>
                </div>

                <?php if ($order['tracking_number']): ?>
                    <div style="margin-top: 16px;">
                        <div class="delivery-info-label">Tracking Number</div>
                        <div style="font-family: 'Courier New', monospace; font-size: 1.1rem; font-weight: 600; color: var(--primary);">
                            <?php echo htmlspecialchars($order['tracking_number']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Timeline -->
        <div class="card">
            <div class="card-header">📅 Order Timeline</div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($timeline as $event): ?>
                        <div class="timeline-item <?php echo in_array($event['status'], ['completed', 'delivered']) ? 'completed' : ''; ?>">
                            <div class="timeline-date"><?php echo date('M j, Y \a\t g:i A', strtotime($event['updated_at'])); ?></div>
                            <div class="timeline-status"><?php echo ucfirst(str_replace('_', ' ', $event['status'])); ?></div>
                            <?php if ($event['notes']): ?>
                                <div class="timeline-note"><?php echo htmlspecialchars($event['notes']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Price Summary -->
        <div class="card">
            <div class="card-header">💰 Price Summary</div>
            <div class="card-body">
                <div class="price-summary">
                    <div class="price-item">
                        <span class="price-label">Subtotal:</span>
                        <span class="price-value">₹<?php echo number_format($order['subtotal'] ?? $order['total_amount'], 0); ?></span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Tax:</span>
                        <span class="price-value">₹<?php echo number_format($order['tax'] ?? 0, 0); ?></span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Shipping:</span>
                        <span class="price-value">₹<?php echo number_format($order['shipping_cost'] ?? 0, 0); ?></span>
                    </div>
                    <div class="price-item">
                        <span class="price-label">Discount:</span>
                        <span class="price-value">- ₹<?php echo number_format($order['discount'] ?? 0, 0); ?></span>
                    </div>
                </div>
                <div style="border-top: 2px solid var(--primary); padding-top: 16px; margin-top: 16px;">
                    <div class="price-item">
                        <span style="font-size: 1.1rem; font-weight: 700;">Total Amount:</span>
                        <span style="font-size: 1.3rem; font-weight: 700; color: var(--primary);">₹<?php echo number_format($order['total_amount'], 0); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 12px; margin-bottom: 40px;">
            <a href="dashboard.php" class="action-button">← Back to Dashboard</a>
            <button class="action-button" style="background: white; color: var(--primary); border: 2px solid var(--primary);" onclick="window.print()">🖨️ Print Order</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
