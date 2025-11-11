<?php
/**
 * Admin Support Tickets Management
 * Manage customer support tickets and inquiries
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';

$message = '';
$message_type = '';

// Handle ticket actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_status') {
            $ticket_id = intval($_POST['ticket_id']);
            $status = trim($_POST['status']);
            $response = trim($_POST['response'] ?? '');
            
            $update = $conn->prepare("
                UPDATE support_tickets 
                SET status = ?, response = ?, responded_by = ?, responded_at = NOW()
                WHERE id = ?
            ");
            $update->bind_param('ssii', $status, $response, $_SESSION['admin_id'], $ticket_id);
            
            if ($update->execute()) {
                $message = 'Ticket updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating ticket!';
                $message_type = 'error';
            }
            $update->close();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';

// Build parameterized query to prevent SQL injection
$query = "SELECT * FROM support_tickets WHERE 1=1";
$types = '';
$params = [];

// Add status filter with proper parameterization
if ($status_filter !== 'all' && !empty($status_filter)) {
    // Whitelist allowed status values for extra security
    $allowed_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if (in_array($status_filter, $allowed_statuses)) {
        $query .= " AND status = ?";
        $types .= 's';
        $params[] = $status_filter;
    }
}

// Add category filter with proper parameterization
if ($category_filter !== 'all' && !empty($category_filter)) {
    // Whitelist allowed categories for extra security
    $allowed_categories = ['general', 'order', 'delivery', 'product', 'refund', 'feedback', 'other'];
    if (in_array($category_filter, $allowed_categories)) {
        $query .= " AND category = ?";
        $types .= 's';
        $params[] = $category_filter;
    }
}

$query .= " ORDER BY created_at DESC LIMIT 100";

// Execute parameterized query
$tickets_result = $conn->prepare($query);
if ($tickets_result && !empty($types)) {
    $tickets_result->bind_param($types, ...$params);
}
$tickets_result->execute();
$tickets = $tickets_result->get_result()->fetch_all(MYSQLI_ASSOC);
$tickets_result->close();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed
    FROM support_tickets
";
$stats = $conn->prepare($stats_query);
$stats->execute();
$stats_data = $stats->get_result()->fetch_assoc();
$stats->close();
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

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--primary);
        text-align: center;
    }

    .stat-card.open {
        border-left-color: var(--warning);
    }

    .stat-card.success {
        border-left-color: var(--success);
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary);
        margin-top: 8px;
    }

    .stat-card.open .stat-value {
        color: var(--warning);
    }

    .stat-card.success .stat-value {
        color: var(--success);
    }

    .card-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 32px;
    }

    .filter-controls {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-select {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: white;
    }

    .filter-btn {
        padding: 8px 16px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }

    .tickets-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .ticket-item {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 16px;
        transition: all 0.3s;
    }

    .ticket-item:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
    }

    .ticket-id {
        font-weight: 600;
        color: var(--primary);
    }

    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-open {
        background: #fef3c7;
        color: #92400e;
    }

    .status-in_progress {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-resolved {
        background: #d1fae5;
        color: #065f46;
    }

    .status-closed {
        background: #e5e7eb;
        color: #374151;
    }

    .ticket-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }

    .meta-item {
        font-size: 0.9rem;
    }

    .meta-label {
        color: var(--text-secondary);
        font-weight: 600;
    }

    .meta-value {
        color: var(--text-primary);
    }

    .ticket-subject {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .ticket-message {
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 12px;
    }

    .ticket-actions {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        padding: 8px 16px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .action-btn:hover {
        background: var(--primary-dark);
    }

    .modal-content {
        border: none;
        border-radius: 12px;
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

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    @media (max-width: 768px) {
        .ticket-header {
            flex-direction: column;
        }

        .ticket-meta {
            grid-template-columns: 1fr;
        }

        .filter-controls {
            flex-direction: column;
        }

        .filter-select,
        .filter-btn {
            width: 100%;
        }
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>💬 Support Tickets</h1>
        <p>Manage customer support inquiries</p>
    </div>
</div>

<div class="container">
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Tickets</div>
            <div class="stat-value"><?php echo $stats_data['total'] ?? 0; ?></div>
        </div>
        <div class="stat-card open">
            <div class="stat-label">Open</div>
            <div class="stat-value"><?php echo $stats_data['open'] ?? 0; ?></div>
        </div>
        <div class="stat-card open">
            <div class="stat-label">In Progress</div>
            <div class="stat-value"><?php echo $stats_data['in_progress'] ?? 0; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Resolved</div>
            <div class="stat-value"><?php echo $stats_data['resolved'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Tickets -->
    <div class="card-section">
        <h2 style="margin: 0 0 20px 0; color: var(--primary);">Tickets</h2>

        <div class="filter-controls">
            <form method="GET" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
                <select name="status" class="filter-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>

                <select name="category" class="filter-select">
                    <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="general" <?php echo $category_filter === 'general' ? 'selected' : ''; ?>>General</option>
                    <option value="order" <?php echo $category_filter === 'order' ? 'selected' : ''; ?>>Order</option>
                    <option value="delivery" <?php echo $category_filter === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                    <option value="product" <?php echo $category_filter === 'product' ? 'selected' : ''; ?>>Product</option>
                    <option value="refund" <?php echo $category_filter === 'refund' ? 'selected' : ''; ?>>Refund</option>
                </select>

                <button type="submit" class="filter-btn">Filter</button>
            </form>
        </div>

        <div class="tickets-list">
            <?php if (!empty($tickets)): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-header">
                            <div>
                                <div class="ticket-id">#{<?php echo str_pad($ticket['id'], 5, '0', STR_PAD_LEFT); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                        </div>

                        <div class="ticket-meta">
                            <div class="meta-item">
                                <div class="meta-label">From</div>
                                <div class="meta-value"><?php echo htmlspecialchars($ticket['name']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Email</div>
                                <div class="meta-value"><?php echo htmlspecialchars($ticket['email']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Category</div>
                                <div class="meta-value"><?php echo ucfirst($ticket['category']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Date</div>
                                <div class="meta-value"><?php echo date('M j, Y H:i', strtotime($ticket['created_at'])); ?></div>
                            </div>
                        </div>

                        <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                        <div class="ticket-message"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></div>

                        <div class="ticket-actions">
                            <button class="action-btn" data-bs-toggle="modal" 
                                onclick="prepareTicket(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['subject']); ?>', '<?php echo htmlspecialchars($ticket['status']); ?>')">
                                ✏️ Update
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    No tickets found
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="ticket_id" id="ticketId">

                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" id="ticketSubject" readonly style="background: #f3f4f6;">
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Response</label>
                        <textarea name="response" placeholder="Your response to the customer..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <button type="submit" class="action-btn" style="width: 100%;">Update Ticket</button>
                        <button type="button" class="action-btn" data-bs-dismiss="modal" style="background: var(--text-secondary); width: 100%;">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function prepareTicket(ticketId, subject, status) {
        document.getElementById('ticketId').value = ticketId;
        document.getElementById('ticketSubject').value = subject;
        const modal = new bootstrap.Modal(document.getElementById('ticketModal'));
        modal.show();
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
