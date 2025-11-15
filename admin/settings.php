<?php
/**
 * Admin Settings Page
 * System configuration and settings management
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';

$message = '';
$message_type = '';

// Get current settings
$settings_query = "SELECT * FROM system_settings LIMIT 1";
$settings = $conn->prepare($settings_query);
$settings->execute();
$settings_data = $settings->get_result()->fetch_assoc();
$settings->close();

// If no settings exist, create defaults
if (!$settings_data) {
    $insert = $conn->prepare("
        INSERT INTO system_settings (
            site_name, site_email, support_phone, low_stock_threshold, 
            order_confirmation_email, status_update_email, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $site_name = 'Books Bookstore';
    $site_email = 'support@bookstore.com';
    $support_phone = '+91-1800-123-4567';
    $low_stock = 10;
    $confirm_email = 1;
    $status_email = 1;
    
    $insert->bind_param('sssiii', $site_name, $site_email, $support_phone, $low_stock, $confirm_email, $status_email);
    $insert->execute();
    $insert->close();
    
    $settings_data = [
        'site_name' => $site_name,
        'site_email' => $site_email,
        'support_phone' => $support_phone,
        'low_stock_threshold' => $low_stock,
        'order_confirmation_email' => $confirm_email,
        'status_update_email' => $status_email
    ];
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $site_name = trim($_POST['site_name']);
    $site_email = trim($_POST['site_email']);
    $support_phone = trim($_POST['support_phone']);
    $low_stock = intval($_POST['low_stock_threshold']);
    $confirm_email = isset($_POST['order_confirmation_email']) ? 1 : 0;
    $status_email = isset($_POST['status_update_email']) ? 1 : 0;

    if (!$settings_data) {
        $insert = $conn->prepare("
            INSERT INTO system_settings (site_name, site_email, support_phone, low_stock_threshold, order_confirmation_email, status_update_email, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $insert->bind_param('sssiii', $site_name, $site_email, $support_phone, $low_stock, $confirm_email, $status_email);
        if ($insert->execute()) {
            $message = 'Settings saved successfully!';
            $message_type = 'success';
            $settings_data = $_POST;
        } else {
            $message = 'Error saving settings!';
            $message_type = 'error';
        }
        $insert->close();
    } else {
        $update = $conn->prepare("
            UPDATE system_settings SET 
            site_name = ?, site_email = ?, support_phone = ?, 
            low_stock_threshold = ?, order_confirmation_email = ?, 
            status_update_email = ?, updated_at = NOW()
        ");
        $update->bind_param('sssiii', $site_name, $site_email, $support_phone, $low_stock, $confirm_email, $status_email);
        if ($update->execute()) {
            $message = 'Settings updated successfully!';
            $message_type = 'success';
            $settings_data['site_name'] = $site_name;
            $settings_data['site_email'] = $site_email;
            $settings_data['support_phone'] = $support_phone;
            $settings_data['low_stock_threshold'] = $low_stock;
            $settings_data['order_confirmation_email'] = $confirm_email;
            $settings_data['status_update_email'] = $status_email;
        } else {
            $message = 'Error updating settings!';
            $message_type = 'error';
        }
        $update->close();
    }
}
?>

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

    .settings-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .settings-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-top: 4px solid var(--primary);
    }

    .card-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 20px 0;
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

    .form-section {
        margin-bottom: 24px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border-color);
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

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 0.95rem;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-group.checkbox {
        display: flex;
        align-items: center;
    }

    .form-group.checkbox input[type="checkbox"] {
        width: auto;
        margin-right: 10px;
        cursor: pointer;
    }

    .form-group.checkbox label {
        margin: 0;
        cursor: pointer;
        font-weight: 500;
    }

    .hint-text {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    .button-group {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .btn {
        padding: 12px 32px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-secondary {
        background: var(--text-secondary);
        color: white;
    }

    .btn-secondary:hover {
        background: #6b7280;
    }

    .info-box {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        border-left: 4px solid var(--primary);
        margin: 20px 0;
    }

    .info-box h4 {
        margin: 0 0 8px 0;
        color: var(--primary);
        font-size: 0.95rem;
    }

    .info-box p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .settings-description {
        color: var(--text-secondary);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }

        .button-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>⚙️ System Settings</h1>
        <p>Configure and manage system settings</p>
    </div>
</div>

<div class="container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- General Settings -->
    <div class="settings-card">
        <h2 class="card-title">🏪 General Settings</h2>

        <p class="settings-description">
            Configure basic information about your bookstore
        </p>

        <form method="POST" action="">
            <!-- Site Information -->
            <div class="form-section">
                <h3 class="section-title">Site Information</h3>

                <div class="form-group">
                    <label for="site_name">Site Name *</label>
                    <input type="text" id="site_name" name="site_name" required 
                        value="<?php echo htmlspecialchars($settings_data['site_name'] ?? ''); ?>">
                    <p class="hint-text">Your store name displayed across the site</p>
                </div>

                <div class="form-group">
                    <label for="site_email">Support Email *</label>
                    <input type="email" id="site_email" name="site_email" required 
                        value="<?php echo htmlspecialchars($settings_data['site_email'] ?? ''); ?>">
                    <p class="hint-text">Email address for customer support</p>
                </div>

                <div class="form-group">
                    <label for="support_phone">Support Phone *</label>
                    <input type="text" id="support_phone" name="support_phone" required 
                        value="<?php echo htmlspecialchars($settings_data['support_phone'] ?? ''); ?>">
                    <p class="hint-text">Customer support phone number</p>
                </div>
            </div>

            <!-- Inventory Settings -->
            <div class="form-section">
                <h3 class="section-title">📦 Inventory Settings</h3>

                <div class="form-group">
                    <label for="low_stock_threshold">Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1" 
                        value="<?php echo intval($settings_data['low_stock_threshold'] ?? 10); ?>">
                    <p class="hint-text">Number of items below which a product is considered low stock</p>
                </div>
            </div>

            <!-- Email Notification Settings -->
            <div class="form-section">
                <h3 class="section-title">📧 Email Notifications</h3>

                <div class="form-group checkbox">
                    <input type="checkbox" id="order_confirmation_email" name="order_confirmation_email"
                        <?php echo ($settings_data['order_confirmation_email'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="order_confirmation_email">Send Order Confirmation Emails</label>
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="status_update_email" name="status_update_email"
                        <?php echo ($settings_data['status_update_email'] ?? 1) ? 'checked' : ''; ?>>
                    <label for="status_update_email">Send Order Status Update Emails</label>
                </div>

                <div class="info-box">
                    <h4>📌 Email Configuration</h4>
                    <p>Email notifications are queued and processed by the background job. Configure SMTP settings in your email service provider to ensure reliable delivery.</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <button type="submit" name="save_settings" class="btn btn-primary">💾 Save Settings</button>
                <button type="reset" class="btn btn-secondary">↺ Reset</button>
            </div>
        </form>
    </div>

    <!-- System Information -->
    <div class="settings-card">
        <h2 class="card-title">ℹ️ System Information</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="info-box">
                <h4>PHP Version</h4>
                <p style="font-family: monospace; color: var(--primary); font-weight: 600;">
                    <?php echo phpversion(); ?>
                </p>
            </div>

            <div class="info-box">
                <h4>MySQL Version</h4>
                <p style="font-family: monospace; color: var(--primary); font-weight: 600;">
                    <?php 
                    $version_stmt = $conn->prepare("SELECT VERSION() as version");
                    $version_stmt->execute();
                    $version_result = $version_stmt->get_result()->fetch_assoc();
                    echo htmlspecialchars($version_result['version']);
                    $version_stmt->close();
                    ?>
                </p>
            </div>

            <div class="info-box">
                <h4>Server Time</h4>
                <p style="font-family: monospace; color: var(--primary); font-weight: 600;">
                    <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>

            <div class="info-box">
                <h4>Database Name</h4>
                <p style="font-family: monospace; color: var(--primary); font-weight: 600;">
                    <?php echo defined('DB_NAME') ? htmlspecialchars(DB_NAME) : 'books_ecom'; ?>
                </p>
            </div>
        </div>

        <!-- Database Statistics -->
        <?php
        $db_stats = $conn->prepare("
            SELECT 
                COUNT(*) as tables,
                SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
        ");
        $db_stats->execute();
        $db_stats_data = $db_stats->get_result()->fetch_assoc();
        $db_stats->close();
        ?>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--border-color);">
            <h3 class="section-title">Database Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 16px;">
                <div class="info-box">
                    <h4>Total Tables</h4>
                    <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 0;">
                        <?php echo $db_stats_data['tables'] ?? 0; ?>
                    </p>
                </div>
                <div class="info-box">
                    <h4>Database Size</h4>
                    <p style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 0;">
                        <?php echo number_format($db_stats_data['size_mb'] ?? 0, 2); ?> MB
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance -->
    <div class="settings-card">
        <h2 class="card-title">🛠️ Maintenance</h2>

        <p class="settings-description">
            Useful tools for system maintenance and troubleshooting
        </p>

        <div class="form-section">
            <h3 class="section-title">Diagnostic Information</h3>

            <div class="info-box">
                <h4>📋 Recommended Checks</h4>
                <p>
                    ✓ Check MySQL connections are working<br>
                    ✓ Verify file permissions (uploads directory)<br>
                    ✓ Test email queue processing<br>
                    ✓ Verify cron jobs are running<br>
                    ✓ Monitor database size and performance<br>
                    ✓ Review error logs regularly
                </p>
            </div>

            <div class="info-box">
                <h4>🔄 Background Jobs</h4>
                <p>
                    Email queue processing runs via cron job. Configure:<br>
                    <code style="background: white; padding: 8px; border-radius: 4px; display: inline-block;">
                        */5 * * * * php /path/to/cron/send-emails.php
                    </code>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/admin_footer.php'; ?>
