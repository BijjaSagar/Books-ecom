<?php
/**
 * Admin Inventory Management
 * Manage product stock levels, low stock alerts, and inventory history
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';

// Handle stock updates
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_stock') {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $reason = trim($_POST['reason']);

            $update = $conn->prepare("
                UPDATE products 
                SET stock_quantity = ? 
                WHERE id = ?
            ");
            $update->bind_param('ii', $quantity, $product_id);

            if ($update->execute()) {
                // Log inventory change
                $log = $conn->prepare("
                    INSERT INTO inventory_log (product_id, action, quantity_changed, reason, admin_id, created_at)
                    VALUES (?, 'update', ?, ?, ?, NOW())
                ");
                $old_qty = intval($_POST['old_quantity']);
                $change = $quantity - $old_qty;
                $log->bind_param('iisi', $product_id, $change, $reason, $_SESSION['admin_id']);
                $log->execute();
                $log->close();

                $message = 'Stock updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating stock!';
                $message_type = 'error';
            }
            $update->close();
        }
    }
}

// Get inventory statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(stock_quantity) as total_stock,
        COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
        COUNT(CASE WHEN stock_quantity <= 10 THEN 1 END) as low_stock
    FROM products
";
$stats = $conn->prepare($stats_query);
$stats->execute();
$stats_data = $stats->get_result()->fetch_assoc();
$stats->close();

// Get products with low stock
$low_stock_query = "
    SELECT p.id, p.title, p.author, p.stock_quantity, p.price, c.name as category
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock_quantity <= 10
    ORDER BY p.stock_quantity ASC
    LIMIT 20
";
$low_stock = $conn->prepare($low_stock_query);
$low_stock->execute();
$low_stock_result = $low_stock->get_result();

// Get all products for inventory table
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$inventory_query = "
    SELECT p.id, p.title, p.author, p.stock_quantity, p.price, c.name as category
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

if ($filter === 'low') {
    $inventory_query .= " AND p.stock_quantity <= 10";
} elseif ($filter === 'out') {
    $inventory_query .= " AND p.stock_quantity = 0";
}

if (!empty($search)) {
    $inventory_query .= " AND (p.title LIKE ? OR p.author LIKE ?)";
}

$inventory_query .= " ORDER BY p.stock_quantity ASC LIMIT 50";

$inventory = $conn->prepare($inventory_query);

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $inventory->bind_param('ss', $search_term, $search_term);
}

$inventory->execute();
$inventory_result = $inventory->get_result();
?>

<style>
    :root {
        --primary: #1e40af;
        --primary-dark: #1e3a8a;
        --secondary: #3b82f6;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --border-color: #e5e7eb;
        --text-primary: #374151;
        --text-secondary: #6b7280;
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--primary);
    }

    .stat-card.warning {
        border-left-color: var(--warning);
    }

    .stat-card.danger {
        border-left-color: var(--danger);
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .stat-card.warning .stat-value {
        color: var(--warning);
    }

    .stat-card.danger .stat-value {
        color: var(--danger);
    }

    .card-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 32px;
    }

    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        margin: 0 0 20px 0;
        color: var(--primary);
        padding-bottom: 12px;
        border-bottom: 2px solid var(--border-color);
    }

    .alert {
        border: none;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .filter-section {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 2px solid var(--border-color);
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        color: var(--text-primary);
    }

    .filter-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .search-input {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
        flex: 1;
        min-width: 200px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: #f3f4f6;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
        font-size: 0.9rem;
    }

    .data-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .data-table tr:hover {
        background: #f9fafb;
    }

    .stock-level {
        font-weight: 600;
    }

    .stock-level.danger {
        color: var(--danger);
    }

    .stock-level.warning {
        color: var(--warning);
    }

    .stock-level.success {
        color: var(--success);
    }

    .action-btn {
        padding: 6px 12px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        transition: all 0.3s;
    }

    .action-btn:hover {
        background: var(--primary-dark);
    }

    .modal-backdrop {
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        color: white;
        border: none;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: var(--text-primary);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    @media (max-width: 768px) {
        .filter-section {
            flex-direction: column;
        }

        .search-input {
            width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>📦 Inventory Management</h1>
        <p>Monitor stock levels and manage product inventory</p>
    </div>
</div>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?php echo $stats_data['total_products']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Stock</div>
            <div class="stat-value"><?php echo $stats_data['total_stock']; ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-value"><?php echo $stats_data['low_stock']; ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Out of Stock</div>
            <div class="stat-value"><?php echo $stats_data['out_of_stock']; ?></div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if ($stats_data['low_stock'] > 0): ?>
        <div class="card-section">
            <h3 class="section-title">⚠️ Low Stock Alert</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $low_stock_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                    <span style="color: var(--text-secondary); font-size: 0.9rem;">by <?php echo htmlspecialchars($row['author']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="stock-level <?php echo $row['stock_quantity'] === 0 ? 'danger' : 'warning'; ?>">
                                        <?php echo $row['stock_quantity']; ?> units
                                    </span>
                                </td>
                                <td>₹<?php echo number_format($row['price'], 0); ?></td>
                                <td>
                                    <button class="action-btn" data-bs-toggle="modal" data-bs-target="#updateStockModal" 
                                        onclick="prepareUpdate(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title']); ?>', <?php echo $row['stock_quantity']; ?>)">
                                        Update Stock
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Inventory -->
    <div class="card-section">
        <h3 class="section-title">📊 Complete Inventory</h3>

        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap; align-items: center;">
                <button type="submit" name="filter" value="all" class="filter-btn <?php echo ($filter === 'all' ? 'active' : ''); ?>">
                    All Products
                </button>
                <button type="submit" name="filter" value="low" class="filter-btn <?php echo ($filter === 'low' ? 'active' : ''); ?>">
                    Low Stock (≤10)
                </button>
                <button type="submit" name="filter" value="out" class="filter-btn <?php echo ($filter === 'out' ? 'active' : ''); ?>">
                    Out of Stock
                </button>
                <input type="text" name="search" class="search-input" placeholder="Search by product name or author..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="action-btn" style="flex-shrink: 0;">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock Level</th>
                        <th>Price</th>
                        <th>Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $inventory_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                <span style="color: var(--text-secondary); font-size: 0.9rem;">by <?php echo htmlspecialchars($row['author']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="stock-level <?php 
                                    if ($row['stock_quantity'] === 0) echo 'danger';
                                    elseif ($row['stock_quantity'] <= 10) echo 'warning';
                                    else echo 'success';
                                ?>">
                                    <?php echo $row['stock_quantity']; ?>
                                    <?php 
                                        if ($row['stock_quantity'] === 0) echo ' <span class="badge badge-danger">OUT</span>';
                                        elseif ($row['stock_quantity'] <= 10) echo ' <span class="badge badge-warning">LOW</span>';
                                    ?>
                                </span>
                            </td>
                            <td>₹<?php echo number_format($row['price'], 0); ?></td>
                            <td>₹<?php echo number_format($row['stock_quantity'] * $row['price'], 0); ?></td>
                            <td>
                                <button class="action-btn" data-bs-toggle="modal" data-bs-target="#updateStockModal"
                                    onclick="prepareUpdate(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['title']); ?>', <?php echo $row['stock_quantity']; ?>)">
                                    Update
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Stock Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="updateForm">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="product_id" id="productId">
                    <input type="hidden" name="old_quantity" id="oldQuantity">

                    <div class="form-group">
                        <label>Product</label>
                        <input type="text" id="productName" readonly style="background: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="text" id="currentStock" readonly style="background: #f3f4f6; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>New Quantity *</label>
                        <input type="number" name="quantity" id="newQuantity" required min="0">
                    </div>

                    <div class="form-group">
                        <label>Reason for Update</label>
                        <select name="reason">
                            <option value="">Select reason</option>
                            <option value="purchase">Purchase/Restocking</option>
                            <option value="damage">Damage/Loss</option>
                            <option value="return">Customer Return</option>
                            <option value="adjustment">Inventory Adjustment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px;">
                        <button type="submit" class="action-btn" style="padding: 10px; width: 100%;">Update Stock</button>
                        <button type="button" class="action-btn" data-bs-dismiss="modal" style="background: var(--text-secondary); padding: 10px; width: 100%;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function prepareUpdate(productId, productName, currentStock) {
        document.getElementById('productId').value = productId;
        document.getElementById('productName').value = productName;
        document.getElementById('currentStock').value = currentStock + ' units';
        document.getElementById('oldQuantity').value = currentStock;
        document.getElementById('newQuantity').value = currentStock;
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
