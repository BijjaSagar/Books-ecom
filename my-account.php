<?php
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

// Handle form submissions
$errors = [];
$success_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profile_data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'country' => trim($_POST['country'] ?? '')
    ];
    
    $update_result = $user_profile->updateProfile($user_id, $profile_data);
    
    if ($update_result['success']) {
        $success_message = $update_result['message'];
        // Update session name if changed
        if (!empty($profile_data['full_name'])) {
            $_SESSION['user_name'] = $profile_data['full_name'];
        }
    } else {
        $errors[] = $update_result['error'];
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password)) {
        $errors[] = "Please enter your current password.";
    }
    if (empty($new_password)) {
        $errors[] = "Please enter a new password.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    
    if (empty($errors)) {
        $password_result = $user_profile->updatePassword($user_id, $current_password, $new_password);
        
        if ($password_result['success']) {
            $success_message = $password_result['message'];
        } else {
            $errors[] = $password_result['error'];
        }
    }
}

// Get user profile data
$user_data = $user_profile->getUserProfile($user_id);

// Get user's order history
$order_history = $user_profile->getOrderHistory($user_id, 5);

?>

<!-- Page Header -->
<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <h1>My Account</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/bookshelf/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Account</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-5">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column: Dashboard Navigation -->
        <div class="col-lg-3">
            <div class="list-group dashboard-nav">
                <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="bi bi-box-seam"></i> Orders
                </a>
                <a href="#profile" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="bi bi-person"></i> Profile Details
                </a>
                <a href="#address" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="bi bi-geo-alt"></i> Address Book
                </a>
                <a href="#wishlist" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="bi bi-heart"></i> Wishlist
                </a>
                <a href="/bookshelf/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- Right Column: Dashboard Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Dashboard Pane -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h5>
                            <p>From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.</p>
                            
                            <!-- Quick Stats -->
                            <div class="row mt-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title"><?php echo count($order_history); ?></h5>
                                            <p class="card-text">Recent Orders</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">0</h5>
                                            <p class="card-text">Wishlist Items</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Member</h5>
                                            <p class="card-text">Since <?php echo date('M Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Pane -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                            <span>Your Orders</span>
                            <a href="/bookshelf/orders-history.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($order_history)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($order_history as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = [
                                                            'pending' => 'bg-warning',
                                                            'processing' => 'bg-info',
                                                            'shipped' => 'bg-primary',
                                                            'delivered' => 'bg-success',
                                                            'cancelled' => 'bg-danger'
                                                        ][$order['order_status']] ?? 'bg-secondary';
                                                        ?>
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucfirst($order['order_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $order['item_count']; ?></td>
                                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <a href="/bookshelf/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-box-seam" style="font-size: 3rem; color: #ddd;"></i>
                                    <p class="mt-3">You have not placed any orders yet.</p>
                                    <a href="/bookshelf/shop.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Details Pane -->
                <div class="tab-pane fade" id="profile">
                    <div class="card">
                        <div class="card-header fw-bold">Profile Details</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user_data['full_name'] ?? $_SESSION['user_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user_data['email'] ?? $_SESSION['user_email']); ?>" readonly>
                                        <small class="text-muted">Email cannot be changed.</small>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="zip_code" class="form-label">ZIP/Postal Code</label>
                                        <input type="text" class="form-control" id="zip_code" name="zip_code" 
                                               value="<?php echo htmlspecialchars($user_data['zip_code'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <hr>
                                <h6 class="mb-3">Change Password</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Address Book Pane -->
                <div class="tab-pane fade" id="address">
                    <div class="card">
                        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                            <span>Address Book</span>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                                <i class="bi bi-plus-lg"></i> Add New Address
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($user_data['address'])): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-3">Default Shipping Address</h6>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </div>
                                    <address>
                                        <strong><?php echo htmlspecialchars($user_data['full_name'] ?? $_SESSION['user_name']); ?></strong><br>
                                        <?php echo htmlspecialchars($user_data['address'] ?? ''); ?><br>
                                        <?php if (!empty($user_data['city'])): ?>
                                            <?php echo htmlspecialchars($user_data['city']); ?>, 
                                        <?php endif; ?>
                                        <?php if (!empty($user_data['state'])): ?>
                                            <?php echo htmlspecialchars($user_data['state']); ?> 
                                        <?php endif; ?>
                                        <?php if (!empty($user_data['zip_code'])): ?>
                                            <?php echo htmlspecialchars($user_data['zip_code']); ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($user_data['country'])): ?>
                                            <?php echo htmlspecialchars($user_data['country']); ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($user_data['phone'])): ?>
                                            Phone: <?php echo htmlspecialchars($user_data['phone']); ?>
                                        <?php endif; ?>
                                    </address>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-geo-alt" style="font-size: 3rem; color: #ddd;"></i>
                                    <p class="mt-3">You haven't added any addresses yet.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                                        <i class="bi bi-plus-lg me-2"></i>Add Address
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Wishlist Pane -->
                <div class="tab-pane fade" id="wishlist">
                    <div class="card">
                        <div class="card-header fw-bold">Wishlist</div>
                        <div class="card-body">
                            <div class="text-center py-5">
                                <i class="bi bi-heart" style="font-size: 3rem; color: #ddd;"></i>
                                <p class="mt-3">Your wishlist is empty.</p>
                                <a href="/bookshelf/shop.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="modal_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="modal_full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="modal_address" class="form-label">Address</label>
                        <textarea class="form-control" id="modal_address" name="address" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modal_city" class="form-label">City</label>
                            <input type="text" class="form-control" id="modal_city" name="city">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modal_state" class="form-label">State</label>
                            <input type="text" class="form-control" id="modal_state" name="state">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modal_zip_code" class="form-label">ZIP/Postal Code</label>
                            <input type="text" class="form-control" id="modal_zip_code" name="zip_code">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modal_country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="modal_country" name="country">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="modal_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="modal_phone" name="phone">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="defaultAddress">
                        <label class="form-check-label" for="defaultAddress">
                            Set as default shipping address
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Save Address</button>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>