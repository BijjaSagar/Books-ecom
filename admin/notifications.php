<?php
/**
 * Admin Email & Notifications Management
 * Monitor email queue and send statistics
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';
require_once '../includes/EmailNotificationManager.php';

$email_manager = new EmailNotificationManager($conn);

// Handle manual email processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'process_queue') {
        $result = $email_manager->processQueue(intval($_POST['batch_size'] ?? 10));
        $_SESSION['message'] = "Processed: " . $result['processed'] . " sent, " . $result['failed'] . " failed";
        $_SESSION['message_type'] = 'success';
        header('Location: notifications.php');
        exit;
    }
}

// Get email statistics
$stats = $email_manager->getStatistics();

// Get queue status
$pending_stmt = $conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'pending'");
$pending_count = $pending_stmt->fetch_assoc()['count'];

$sent_stmt = $conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'sent'");
$sent_count = $sent_stmt->fetch_assoc()['count'];

$failed_stmt = $conn->query("SELECT COUNT(*) as count FROM email_queue WHERE status = 'failed'");
$failed_count = $failed_stmt->fetch_assoc()['count'];

// Get recent emails
$recent_emails = $conn->query(
    "SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 20"
);
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

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        background: var(--primary);
        color: white;
    }

    .btn:hover {
        background: var(--primary-dark);
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-sent {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-failed {
        background: #fee2e2;
        color: #991b1b;
    }

    .email-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .email-table th {
        background: #f3f4f6;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
    }

    .email-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
    }

    .email-table tr:hover {
        background: #f9fafb;
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

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>📧 Email & Notifications</h1>
        <p>Manage email queue and notifications</p>
    </div>
</div>

<div class="container">
    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Queue Status Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $pending_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sent</div>
            <div class="stat-value"><?php echo $sent_count; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-value"><?php echo $failed_count; ?></div>
        </div>
    </div>

    <!-- Process Queue Section -->
    <div class="card">
        <h2>⚙️ Process Email Queue</h2>
        <p>Manually process pending emails in the queue.</p>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Batch Size (emails to send at once)</label>
                    <input type="number" name="batch_size" value="10" min="1" max="100">
                </div>
            </div>
            <button type="submit" name="action" value="process_queue" class="btn">
                🚀 Process Queue
            </button>
        </form>
    </div>

    <!-- Email Statistics -->
    <div class="card">
        <h2>📊 Email Statistics</h2>
        <div class="stats-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-label">Total Sent</div>
                <div class="stat-value"><?php echo $stats['total_sent'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Currently Pending</div>
                <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Failed</div>
                <div class="stat-value"><?php echo $stats['failed'] ?? 0; ?></div>
            </div>
        </div>

        <h3 style="margin-top: 24px; color: var(--text-primary);">By Type:</h3>
        <div style="display: grid; gap: 12px;">
            <?php foreach (($stats['by_type'] ?? []) as $type => $count): ?>
                <div style="display: flex; justify-content: space-between; padding: 12px; background: #f9fafb; border-radius: 6px;">
                    <span style="font-weight: 500;"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></span>
                    <span style="background: var(--primary); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;">
                        <?php echo $count; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Emails -->
    <div class="card">
        <h2>📨 Recent Emails</h2>
        <div style="overflow-x: auto;">
            <table class="email-table">
                <thead>
                    <tr>
                        <th>To Email</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($email = $recent_emails->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span style="font-size: 0.9rem; color: var(--text-secondary);">
                                    <?php echo htmlspecialchars(substr($email['to_email'], 0, 30)); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($email['subject'], 0, 40)); ?></td>
                            <td>
                                <span style="font-size: 0.85rem;">
                                    <?php echo ucfirst(str_replace('_', ' ', $email['email_type'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($email['status']); ?>">
                                    <?php echo ucfirst($email['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $email['attempts']; ?></td>
                            <td style="color: var(--text-secondary); font-size: 0.9rem;">
                                <?php echo date('M d, H:i', strtotime($email['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Email Configuration Tips -->
    <div class="card" style="background: #eff6ff; border-left: 4px solid var(--info);">
        <h2 style="color: var(--primary);">💡 Configuration Tips</h2>
        <ul style="color: var(--text-primary); line-height: 1.8;">
            <li>To enable automatic email sending, set up a cron job to call <code>admin/send-emails-cron.php</code> every 5 minutes</li>
            <li>Configure SMTP settings in your <code>.env</code> file</li>
            <li>Monitor the email queue regularly to ensure emails are being delivered</li>
            <li>Failed emails will retry automatically (up to 3 attempts)</li>
            <li>Review email logs for troubleshooting delivery issues</li>
        </ul>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
