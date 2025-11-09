<?php
// customer-dashboard.php - Comprehensive customer dashboard
include 'includes/header.php';
require_once 'includes/UserProfile.php';

// --- Page Protection ---
// If the user is NOT logged in, redirect them to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: /bookshelf/login.php");
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Initialize user profile system
$user_profile = new UserProfile($conn);

// Get user profile data
$user_data = $user_profile->getUserProfile($user_id);

// Get user's order history
$order_history = $user_profile->getOrderHistory($user_id, 10);

// Get user's wishlist
$wishlist = $user_profile->getWishlist($user_id);

// Calculate dashboard statistics
$total_orders = count($order_history);
$total_spent = array_sum(array_column($order_history, 'total_amount'));
$wishlist_count = count($wishlist);

// Get recent orders (last 5)
$recent_orders = array_slice($order_history, 0, 5);

$page_title_override = "Customer Dashboard - Bookory";
?>

<!-- Page Header -->
<div class="page-header bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="lead mb-0">Manage your account, orders, and preferences all in one place.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex justify-content-md-end">
                    <div class="text-center me-4">
                        <div class="fs-2 fw-bold"><?php echo $total_orders; ?></div>
                        <div class="small">Orders</div>
                    </div>
                    <div class="text-center me-4">
                        <div class="fs-2 fw-bold">₹<?php echo number_format($total_spent, 2); ?></div>
                        <div class="small">Spent</div>
                    </div>
                    <div class="text-center">
                        <div class="fs-2 fw-bold"><?php echo $wishlist_count; ?></div>
                        <div class="small">Wishlist</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <!-- Left Column: Quick Actions -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/bookshelf/shop.php" class="btn btn-outline-primary">
                            <i class="bi bi-shop me-2"></i>Continue Shopping
                        </a>
                        <a href="/bookshelf/my-account.php#orders" class="btn btn-outline-success">
                            <i class="bi bi-box-seam me-2"></i>View All Orders
                        </a>
                        <a href="/bookshelf/my-account.php#profile" class="btn btn-outline-info">
                            <i class="bi bi-person me-2"></i>Edit Profile
                        </a>
                        <a href="/bookshelf/my-account.php#address" class="btn btn-outline-warning">
                            <i class="bi bi-geo-alt me-2"></i>Manage Addresses
                        </a>
                        <a href="/bookshelf/my-account.php#wishlist" class="btn btn-outline-danger">
                            <i class="bi bi-heart me-2"></i>View Wishlist
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Account Summary -->
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-person-badge me-2"></i>Account Summary</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <small class="text-muted">Member Since</small>
                            <div class="fw-bold"><?php echo date('F Y', strtotime($user_data['created_at'] ?? 'now')); ?></div>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted">Email</small>
                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($user_data['email'] ?? $_SESSION['user_email']); ?></div>
                        </li>
                        <li class="mb-2">
                            <small class="text-muted">Phone</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($user_data['phone'] ?? 'Not provided'); ?></div>
                        </li>
                        <li>
                            <small class="text-muted">Last Login</small>
                            <div class="fw-bold"><?php echo date('M j, Y'); ?></div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Dashboard Content -->
        <div class="col-lg-9">
            <!-- Recent Orders -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Recent Orders</h5>
                    <a href="/bookshelf/my-account.php#orders" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                                <small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo $order['item_count']; ?> items</td>
                                            <td>
                                                <?php
                                                $status_config = [
                                                    'pending' => ['bg-warning', '.Pending'],
                                                    'processing' => ['bg-info', 'Processing'],
                                                    'shipped' => ['bg-primary', 'Shipped'],
                                                    'delivered' => ['bg-success', 'Delivered'],
                                                    'cancelled' => ['bg-danger', 'Cancelled']
                                                ];
                                                $status_info = $status_config[$order['order_status']] ?? ['bg-secondary', ucfirst($order['order_status'])];
                                                ?>
                                                <span class="badge <?php echo $status_info[0]; ?>"><?php echo $status_info[1]; ?></span>
                                            </td>
                                            <td class="fw-bold text-success">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <a href="/bookshelf/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam" style="font-size: 3rem; color: #ddd;"></i>
                            <p class="mt-3 mb-0">You haven't placed any orders yet.</p>
                            <a href="/bookshelf/shop.php" class="btn btn-primary mt-2">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Wishlist Preview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-heart me-2"></i>Wishlist</h5>
                    <a href="/bookshelf/my-account.php#wishlist" class="btn btn-sm btn-outline-danger">View All</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($wishlist)): ?>
                        <div class="row">
                            <?php foreach(array_slice($wishlist, 0, 4) as $item): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <?php if (!empty($item['cover_image'])): ?>
                                            <img src="/bookshelf/public/images/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 style="height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                                <i class="bi bi-book" style="font-size: 2rem; color: #ccc;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body p-2">
                                            <h6 class="card-title fs-6 text-truncate"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <p class="card-text mb-1 fw-bold text-success">₹<?php echo number_format($item['price'], 2); ?></p>
                                            <button class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-cart me-1"></i>Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-heart" style="font-size: 3rem; color: #ddd;"></i>
                            <p class="mt-3 mb-0">Your wishlist is empty.</p>
                            <a href="/bookshelf/shop.php" class="btn btn-outline-danger mt-2">Browse Books</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Statistics -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Order Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <div class="fs-4 fw-bold text-primary"><?php echo $total_orders; ?></div>
                                    <div class="small text-muted">Total Orders</div>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-success">₹<?php echo number_format($total_spent, 2); ?></div>
                                    <div class="small text-muted">Total Spent</div>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-info"><?php echo $wishlist_count; ?></div>
                                    <div class="small text-muted">Wishlist</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small">Monthly Average</span>
                                    <span class="small fw-bold">₹<?php echo $total_orders > 0 ? number_format($total_spent / max(1, date('n', strtotime($user_data['created_at'] ?? 'now'))), 2) : '0.00'; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo min(100, ($total_orders / max(1, (time() - strtotime($user_data['created_at'] ?? 'now')) / (30 * 24 * 60 * 60))) * 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0"><i class="bi bi-gift me-2"></i>Special Offers</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <h6 class="alert-heading"><i class="bi bi-stars me-2"></i>Exclusive Member Discount!</h6>
                                <p class="mb-0">Get 15% off your next order with code: <strong>MEMBER15</strong></p>
                            </div>
                            <div class="alert alert-warning mb-0">
                                <h6 class="alert-heading"><i class="bi bi-calendar-event me-2"></i>Upcoming Sale</h6>
                                <p class="mb-0">Summer Book Sale starts next week. Up to 40% off selected titles!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>