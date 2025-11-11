<?php
/**
 * Admin Export Management
 * Generate and download data exports in multiple formats
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';
require_once '../includes/ExportManager.php';

$export_manager = new ExportManager($conn, '₹');

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_type'])) {
    $export_type = $_POST['export_type'];
    $format = $_POST['format'] ?? 'csv';

    $filename = '';
    $content = '';

    switch ($export_type) {
        case 'orders':
            $filters = [
                'status' => $_POST['order_status'] ?? '',
                'start_date' => $_POST['start_date'] ?? '',
                'end_date' => $_POST['end_date'] ?? ''
            ];
            if ($format === 'csv') {
                $content = $export_manager->exportOrdersCSV($filters);
            } else {
                $content = $export_manager->exportOrdersExcel($filters);
            }
            $filename = 'orders-' . date('Y-m-d') . '.' . $format;
            break;

        case 'sales_report':
            $start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $_POST['end_date'] ?? date('Y-m-d');
            $content = $export_manager->exportSalesReportCSV($start_date, $end_date);
            $filename = 'sales-report-' . $start_date . '-to-' . $end_date . '.csv';
            break;

        case 'products':
            $content = $export_manager->exportProductsCSV();
            $filename = 'products-' . date('Y-m-d') . '.csv';
            break;

        case 'customers':
            $content = $export_manager->exportCustomersCSV();
            $filename = 'customers-' . date('Y-m-d') . '.csv';
            break;

        case 'order_detail':
            $order_id = intval($_POST['order_id']);
            $content = $export_manager->exportOrderDetailCSV($order_id);
            $filename = 'order-' . str_pad($order_id, 4, '0', STR_PAD_LEFT) . '.csv';
            break;
    }

    if ($content) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo $content;
        exit;
    }
}
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

    .export-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .export-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-top: 4px solid var(--primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .export-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15);
    }

    .export-icon {
        font-size: 2.5rem;
        margin-bottom: 12px;
    }

    .export-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 12px 0;
    }

    .export-desc {
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        background: var(--primary);
        color: white;
        font-size: 0.95rem;
    }

    .btn:hover {
        background: var(--primary-dark);
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
        margin: 5% auto;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        max-height: 90vh;
        overflow-y: auto;
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
    .form-group select {
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

    .format-options {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }

    .format-option {
        flex: 1;
    }

    .format-option label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 12px;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .format-option input:checked + label {
        border-color: var(--primary);
        background: #eff6ff;
    }

    .format-option input {
        margin: 0;
    }

    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }

    .card h2 {
        margin-top: 0;
        color: var(--primary);
    }

    @media (max-width: 768px) {
        .export-grid {
            grid-template-columns: 1fr;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .format-options {
            flex-direction: column;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>📥 Data Export</h1>
        <p>Export your business data in multiple formats</p>
    </div>
</div>

<div class="container">
    <div class="alert alert-info">
        💡 Select an export type below to download your data as CSV or Excel format. Use filters to customize what data you want to export.
    </div>

    <!-- Export Cards -->
    <div class="export-grid">
        <!-- Orders Export -->
        <div class="export-card" onclick="openExportModal('orders')">
            <div class="export-icon">📦</div>
            <h3 class="export-title">Orders</h3>
            <p class="export-desc">Export all orders with customer details, amounts, and status</p>
            <button class="btn" style="margin-top: 16px; width: 100%;">Export</button>
        </div>

        <!-- Sales Report -->
        <div class="export-card" onclick="openExportModal('sales_report')">
            <div class="export-icon">📊</div>
            <h3 class="export-title">Sales Report</h3>
            <p class="export-desc">Daily sales breakdown with revenue and order counts</p>
            <button class="btn" style="margin-top: 16px; width: 100%;">Export</button>
        </div>

        <!-- Products Export -->
        <div class="export-card" onclick="openExportModal('products')">
            <div class="export-icon">📚</div>
            <h3 class="export-title">Products</h3>
            <p class="export-desc">Export all products with pricing, stock, and ratings</p>
            <button class="btn" style="margin-top: 16px; width: 100%;">Export</button>
        </div>

        <!-- Customers Export -->
        <div class="export-card" onclick="openExportModal('customers')">
            <div class="export-icon">👥</div>
            <h3 class="export-title">Customers</h3>
            <p class="export-desc">Export customer list with order history and spending</p>
            <button class="btn" style="margin-top: 16px; width: 100%;">Export</button>
        </div>
    </div>

    <!-- Recent Orders Export -->
    <div class="card">
        <h2>🎯 Export Specific Order</h2>
        <p>Download detailed information for a single order including all items and details.</p>
        <form method="POST">
            <div class="form-group">
                <label>Order ID *</label>
                <input type="number" name="order_id" required min="1" placeholder="Enter order number">
            </div>
            <button type="submit" name="export_type" value="order_detail" class="btn">Download Order Details</button>
        </form>
    </div>

    <!-- Export History -->
    <div class="card">
        <h2>📋 Export Guide</h2>
        <div style="line-height: 1.8; color: var(--text-primary);">
            <h3 style="margin-top: 16px; margin-bottom: 12px; color: var(--primary);">📦 Orders Export</h3>
            <ul>
                <li>Includes: Order ID, Number, Customer, Email, Total, Status, Date</li>
                <li>Filters: By status, date range</li>
                <li>Best for: Order tracking, fulfillment, analysis</li>
            </ul>

            <h3 style="margin-top: 16px; margin-bottom: 12px; color: var(--primary);">📊 Sales Report</h3>
            <ul>
                <li>Includes: Daily sales, order counts, average values, customer counts</li>
                <li>Filters: Custom date range</li>
                <li>Best for: Revenue analysis, trend analysis, business metrics</li>
            </ul>

            <h3 style="margin-top: 16px; margin-bottom: 12px; color: var(--primary);">📚 Products Export</h3>
            <ul>
                <li>Includes: ID, Title, Author, Price, Stock, Status, Rating, Reviews</li>
                <li>Filters: None (complete product list)</li>
                <li>Best for: Inventory management, pricing updates, listings</li>
            </ul>

            <h3 style="margin-top: 16px; margin-bottom: 12px; color: var(--primary);">👥 Customers Export</h3>
            <ul>
                <li>Includes: Name, Email, Phone, Registration Date, Orders, Spending</li>
                <li>Filters: None (complete customer list)</li>
                <li>Best for: Marketing, segmentation, loyalty programs</li>
            </ul>
        </div>
    </div>
</div>

<!-- Orders Export Modal -->
<div id="ordersModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📦 Export Orders</h2>
            <span class="close" onclick="closeModal('ordersModal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="export_type" value="orders">

            <div class="format-options">
                <div class="format-option">
                    <input type="radio" name="format" value="csv" id="csv1" checked>
                    <label for="csv1">📄 CSV</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="format" value="xlsx" id="xlsx1">
                    <label for="xlsx1">📊 Excel</label>
                </div>
            </div>

            <div class="form-group">
                <label>Filter by Status (Optional)</label>
                <select name="order_status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>From Date (Optional)</label>
                    <input type="date" name="start_date">
                </div>
                <div class="form-group">
                    <label>To Date (Optional)</label>
                    <input type="date" name="end_date">
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%;">⬇️ Download</button>
        </form>
    </div>
</div>

<!-- Sales Report Export Modal -->
<div id="salesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📊 Export Sales Report</h2>
            <span class="close" onclick="closeModal('salesModal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="export_type" value="sales_report">

            <div class="format-options">
                <div class="format-option">
                    <input type="radio" name="format" value="csv" id="csv2" checked>
                    <label for="csv2">📄 CSV</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="format" value="xlsx" id="xlsx2">
                    <label for="xlsx2">📊 Excel</label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%;">⬇️ Download</button>
        </form>
    </div>
</div>

<!-- Products Export Modal -->
<div id="productsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📚 Export Products</h2>
            <span class="close" onclick="closeModal('productsModal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="export_type" value="products">

            <div class="format-options">
                <div class="format-option">
                    <input type="radio" name="format" value="csv" id="csv3" checked>
                    <label for="csv3">📄 CSV</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="format" value="xlsx" id="xlsx3">
                    <label for="xlsx3">📊 Excel</label>
                </div>
            </div>

            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                This will export all your products with current pricing, stock levels, and ratings.
            </p>

            <button type="submit" class="btn" style="width: 100%;">⬇️ Download</button>
        </form>
    </div>
</div>

<!-- Customers Export Modal -->
<div id="customersModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>👥 Export Customers</h2>
            <span class="close" onclick="closeModal('customersModal')">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="export_type" value="customers">

            <div class="format-options">
                <div class="format-option">
                    <input type="radio" name="format" value="csv" id="csv4" checked>
                    <label for="csv4">📄 CSV</label>
                </div>
                <div class="format-option">
                    <input type="radio" name="format" value="xlsx" id="xlsx4">
                    <label for="xlsx4">📊 Excel</label>
                </div>
            </div>

            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                This will export all customer information including order history and total spending.
            </p>

            <button type="submit" class="btn" style="width: 100%;">⬇️ Download</button>
        </form>
    </div>
</div>

<script>
    function openExportModal(type) {
        let modalId = '';
        switch(type) {
            case 'orders':
                modalId = 'ordersModal';
                break;
            case 'sales_report':
                modalId = 'salesModal';
                break;
            case 'products':
                modalId = 'productsModal';
                break;
            case 'customers':
                modalId = 'customersModal';
                break;
        }
        if (modalId) {
            document.getElementById(modalId).classList.add('show');
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
        }
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
