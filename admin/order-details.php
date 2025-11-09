<?php
include '../includes/admin_header.php';

// Get the Order ID from the URL
$order_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    echo "Invalid Order ID.";
    exit();
}

// --- Fetch main order details, including user info ---
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result_order = $stmt->get_result();
if ($result_order->num_rows === 0) {
    echo "Order not found.";
    exit();
}
$order = $result_order->fetch_assoc();
$stmt->close();

// --- Fetch all items for this order ---
$stmt = $conn->prepare("SELECT oi.quantity, oi.price, p.title 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result_items = $stmt->get_result();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Order Details: #<?php echo $order['id']; ?></h1>
    <a href="orders.php" class="btn btn-outline-secondary">Back to Orders</a>
</div>

<div class="row">
    <!-- Left Column: Order Summary & Items -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">Order Items</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php while($item = $result_items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Grand Total</td>
                            <td class="fw-bold">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Customer & Shipping Info -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">Customer Details</div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Shipping Address</div>
            <div class="card-body">
                <!-- In a real app, this would be formatted better from structured address fields -->
                <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
            </div>
        </div>
    </div>
</div>


<?php
// We don't need a fancy footer, just the closing tags
?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>