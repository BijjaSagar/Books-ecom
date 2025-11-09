<?php
// order-confirmation.php - Order confirmation page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user came from successful checkout
if (!isset($_SESSION['order_success'])) {
    header("Location: /bookshelf/");
    exit();
}

$order_data = $_SESSION['order_success'];
$page_title_override = "Order Confirmation - Bookory";

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_override; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .order-confirmation-page {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .confirmation-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }

        .confirmation-header {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
        }

        .order-details {
            padding: 2rem;
        }

        .detail-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .action-buttons {
            text-align: center;
            padding: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
        }

        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            margin: 0.5rem;
        }

        .next-steps {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>

<div class="order-confirmation-page">
    <div class="container">
        <div class="confirmation-container">
            <!-- Success Header -->
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h1 class="h2 fw-bold mb-3">Order Placed Successfully!</h1>
                <p class="lead mb-0">Thank you for your purchase. Your order has been confirmed.</p>
            </div>

            <!-- Order Details -->
            <div class="order-details">
                <h3 class="h4 fw-bold mb-3">Order Details</h3>
                
                <div class="detail-card">
                    <h5 class="fw-semibold mb-3">Order Information</h5>
                    <div class="detail-row">
                        <span>Order Number:</span>
                        <strong><?php echo htmlspecialchars($order_data['order_number']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Order Date:</span>
                        <span><?php echo date('F j, Y, g:i A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Total Amount:</span>
                        <strong class="text-success">₹<?php echo number_format($order_data['total'], 2); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Payment Status:</span>
                        <span class="badge bg-success">Confirmed</span>
                    </div>
                </div>

                <div class="detail-card">
                    <h5 class="fw-semibold mb-3">Shipping Information</h5>
                    <div class="detail-row">
                        <span>Email:</span>
                        <span><?php echo htmlspecialchars($order_data['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Shipping to:</span>
                        <span><?php echo htmlspecialchars($order_data['state'] . ', ' . $order_data['country']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Estimated Delivery:</span>
                        <span><?php echo date('F j, Y', strtotime('+5 days')); ?></span>
                    </div>
                </div>

                <div class="next-steps">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-info-circle me-2"></i>What's Next?</h6>
                    <ul class="mb-0 small">
                        <li>You'll receive an email confirmation shortly</li>
                        <li>We'll notify you when your order ships</li>
                        <li>Track your order anytime in "My Orders"</li>
                        <li>Contact us if you have any questions</li>
                    </ul>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="/bookshelf/my-orders.php" class="btn btn-primary">
                    <i class="bi bi-list-ul me-2"></i>View My Orders
                </a>
                <a href="/bookshelf/shop.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                </a>
                <a href="/bookshelf/" class="btn btn-outline-secondary">
                    <i class="bi bi-house me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php 
// Clear the order success data
unset($_SESSION['order_success']);
include 'includes/footer.php'; 
?>

<!-- =================================================================== -->
<!-- my-orders.php - Customer order history page -->
<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title_override = "My Orders - Bookory";

// Simple authentication check (you might want to implement proper user auth)
if (!isset($_SESSION['customer_email'])) {
    // For demo purposes, we'll use email from session or redirect to login
    if (isset($_GET['email'])) {
        $_SESSION['customer_email'] = $_GET['email'];
    } else {
        // In a real system, redirect to login page
        echo "<div class='container mt-5'><div class='alert alert-warning'>Please log in to view your orders.</div></div>";
        include 'includes/footer.php';
        exit();
    }
}

$customer_email = $_SESSION['customer_email'];

// Get customer orders
$orders_query = "SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("s", $customer_email);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_override; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .orders-page {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .order-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 1.5rem;
        }

        .order-body {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .order-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .track-btn {
            background: linear-gradient(135deg, #10b981, #34d399);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<div class="orders-page">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-4">My Orders</h1>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bag-x display-1 text-muted"></i>
                        <h3 class="h4 mt-3">No orders yet</h3>
                        <p class="text-muted">When you place your first order, it will appear here.</p>
                        <a href="/bookshelf/shop.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-0">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                                        <small>Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></small>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <?php
                                // Get order items
                                $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                                $items_stmt = $conn->prepare($items_query);
                                $items_stmt->bind_param("i", $order['id']);
                                $items_stmt->execute();
                                $items_result = $items_stmt->get_result();
                                $items = $items_result->fetch_all(MYSQLI_ASSOC);
                                ?>
                                
                                <?php foreach ($items as $item): ?>
                                    <div class="order-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_title']); ?></h6>
                                                <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <span class="fw-semibold">$<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <span class="fw-bold">$<?php echo number_format($item['total'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="row mt-3 pt-3 border-top">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Shipping Address:</strong></p>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($order['city'] . ', ' . $order['state_name'] . ', ' . $order['country_name']); ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p class="mb-1"><strong>Total: $<?php echo number_format($order['total_amount'], 2); ?></strong></p>
                                        <?php if ($order['order_status'] === 'shipped'): ?>
                                            <button class="track-btn">
                                                <i class="bi bi-truck me-1"></i>Track Package
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php include 'includes/footer.php'; ?>

<!-- =================================================================== -->
<!-- admin-dashboard.php - Simple admin panel for order management -->
<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Simple admin authentication (in production, use proper authentication)
$admin_password = 'admin123'; // Change this!
if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['admin_password']) && $_POST['admin_password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Admin Login</h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="admin_password" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// Handle order status updates
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $update_stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();
    
    $success_message = "Order status updated successfully!";
}

// Get all orders
$orders_query = "SELECT * FROM orders ORDER BY created_at DESC";
$orders_result = $conn->query($orders_query);
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);

$page_title_override = "Admin Dashboard - Bookory";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_override; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-dashboard {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem 0;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .order-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="admin-dashboard">
    <!-- Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h2 mb-0">Admin Dashboard</h1>
                    <p class="mb-0 opacity-75">Manage your bookstore orders</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="?logout=1" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary text-white">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <h3><?php echo count($orders); ?></h3>
                    <p class="text-muted mb-0">Total Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success text-white">
                        <i class="bi bi-currency-rupee"></i>
                    </div>
                    <h3>$<?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></h3>
                    <p class="text-muted mb-0">Total Revenue</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning text-white">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3><?php echo count(array_filter($orders, fn($o) => $o['order_status'] === 'pending')); ?></h3>
                    <p class="text-muted mb-0">Pending Orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info text-white">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3><?php echo count(array_filter($orders, fn($o) => $o['order_status'] === 'shipped')); ?></h3>
                    <p class="text-muted mb-0">Shipped Orders</p>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="order-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($order['order_status']) {
                                            'pending' => 'warning',
                                            'processing' => 'primary',
                                            'shipped' => 'info',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="new_status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header("Location: /bookshelf/admin-dashboard.php");
    exit();
}
?>