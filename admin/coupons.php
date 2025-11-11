<?php
/**
 * Admin Coupon Management
 * Create, edit, and manage discount coupons
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';
require_once '../includes/CouponManager.php';

$coupon_manager = new CouponManager($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $result = $coupon_manager->createCoupon([
                'code' => strtoupper($_POST['code']),
                'description' => $_POST['description'],
                'discount_type' => $_POST['discount_type'],
                'discount_value' => floatval($_POST['discount_value']),
                'min_order_value' => floatval($_POST['min_order_value'] ?? 0),
                'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
                'per_customer_limit' => intval($_POST['per_customer_limit'] ?? 1),
                'valid_from' => $_POST['valid_from'],
                'valid_until' => $_POST['valid_until'],
                'created_by' => $_SESSION['admin_id']
            ]);
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] === 'update') {
            $result = $coupon_manager->updateCoupon(intval($_POST['coupon_id']), [
                'description' => $_POST['description'],
                'discount_type' => $_POST['discount_type'],
                'discount_value' => floatval($_POST['discount_value']),
                'min_order_value' => floatval($_POST['min_order_value'] ?? 0),
                'valid_until' => $_POST['valid_until'],
                'status' => $_POST['status']
            ]);
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] === 'delete') {
            $result = $coupon_manager->deleteCoupon(intval($_POST['coupon_id']));
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        }
        header('Location: coupons.php');
        exit;
    }
}

$coupons = $coupon_manager->getAllCoupons();
$analytics = $coupon_manager->getAnalytics();
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

    .btn-primary {
        background: var(--primary);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #6ee7b7;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal.show {
        display: block;
    }

    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-header h2 {
        margin: 0;
        color: var(--primary);
    }

    .close {
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 24px;
        font-weight: bold;
    }

    .close:hover {
        color: var(--text-primary);
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 1rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
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
    }

    .coupon-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .coupon-table th {
        background: #f3f4f6;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
    }

    .coupon-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }

    .coupon-table tr:hover {
        background: #f9fafb;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-active {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-inactive {
        background: #f3f4f6;
        color: #6b7280;
    }

    .actions {
        display: flex;
        gap: 8px;
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>🎟️ Coupon Management</h1>
        <p>Create and manage discount coupons</p>
    </div>
</div>

<div class="container">
    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Coupons</div>
            <div class="stat-value"><?php echo $analytics['total_coupons']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Coupons</div>
            <div class="stat-value"><?php echo $analytics['active_coupons']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Discounts</div>
            <div class="stat-value">₹<?php echo number_format($analytics['total_discounts'], 0); ?></div>
        </div>
    </div>

    <!-- Create Button -->
    <div style="margin-bottom: 24px;">
        <button class="btn-primary" onclick="openCreateCouponModal()">➕ Create New Coupon</button>
    </div>

    <!-- Coupons Table -->
    <div style="overflow-x: auto;">
        <table class="coupon-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Discount</th>
                    <th>Min Order</th>
                    <th>Uses</th>
                    <th>Status</th>
                    <th>Valid Until</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                        <td><?php echo ucfirst($coupon['discount_type']); ?></td>
                        <td>
                            <?php echo $coupon['discount_type'] === 'percentage'
                                ? $coupon['discount_value'] . '%'
                                : '₹' . number_format($coupon['discount_value'], 2); ?>
                        </td>
                        <td>₹<?php echo number_format($coupon['min_order_value'], 0); ?></td>
                        <td>
                            <?php echo $coupon['current_uses']; ?>
                            <?php if ($coupon['max_uses']): ?>
                                / <?php echo $coupon['max_uses']; ?>
                            <?php else: ?>
                                / ∞
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $coupon['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo ucfirst($coupon['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($coupon['valid_until'])); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn-primary btn-sm" onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)">Edit</button>
                                <button class="btn-danger btn-sm" onclick="deleteCoupon(<?php echo $coupon['id']; ?>)">Delete</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="couponModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Coupon</h2>
            <span class="close" onclick="closeCouponModal()">&times;</span>
        </div>
        <form method="POST" id="couponForm">
            <input type="hidden" name="action" id="modalAction" value="create">
            <input type="hidden" name="coupon_id" id="couponId">

            <div class="form-group">
                <label>Coupon Code *</label>
                <input type="text" name="code" id="code" required pattern="[A-Z0-9\-_]+" placeholder="e.g., SAVE20">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="description" rows="2" placeholder="Optional description"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" id="discountType" required>
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount (₹)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Discount Value *</label>
                    <input type="number" name="discount_value" id="discountValue" required step="0.01" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Order Value</label>
                    <input type="number" name="min_order_value" id="minOrderValue" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Max Uses (Leave empty for unlimited)</label>
                    <input type="number" name="max_uses" id="maxUses" min="1">
                </div>
            </div>

            <div class="form-group">
                <label>Uses Per Customer</label>
                <input type="number" name="per_customer_limit" id="perCustomerLimit" value="1" min="1">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Valid From *</label>
                    <input type="datetime-local" name="valid_from" id="validFrom" required>
                </div>
                <div class="form-group">
                    <label>Valid Until *</label>
                    <input type="datetime-local" name="valid_until" id="validUntil" required>
                </div>
            </div>

            <div id="statusField" class="form-group" style="display: none;">
                <label>Status</label>
                <select name="status" id="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn-primary">Save Coupon</button>
                <button type="button" class="btn-primary" style="background: #6b7280;" onclick="closeCouponModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateCouponModal() {
        document.getElementById('modalTitle').textContent = 'Create New Coupon';
        document.getElementById('modalAction').value = 'create';
        document.getElementById('couponForm').reset();
        document.getElementById('statusField').style.display = 'none';
        document.getElementById('code').readOnly = false;
        document.getElementById('couponModal').classList.add('show');
    }

    function editCoupon(coupon) {
        document.getElementById('modalTitle').textContent = 'Edit Coupon';
        document.getElementById('modalAction').value = 'update';
        document.getElementById('couponId').value = coupon.id;
        document.getElementById('code').value = coupon.code;
        document.getElementById('code').readOnly = true;
        document.getElementById('description').value = coupon.description || '';
        document.getElementById('discountType').value = coupon.discount_type;
        document.getElementById('discountValue').value = coupon.discount_value;
        document.getElementById('minOrderValue').value = coupon.min_order_value || 0;
        document.getElementById('validUntil').value = coupon.valid_until.replace(' ', 'T');
        document.getElementById('status').value = coupon.status;
        document.getElementById('statusField').style.display = 'block';
        document.getElementById('couponModal').classList.add('show');
    }

    function closeCouponModal() {
        document.getElementById('couponModal').classList.remove('show');
    }

    function deleteCoupon(couponId) {
        if (confirm('Are you sure you want to delete this coupon?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="coupon_id" value="${couponId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('couponModal');
        if (event.target === modal) {
            closeCouponModal();
        }
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
