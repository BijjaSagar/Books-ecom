<?php
/**
 * Admin Review Moderation
 * Moderate customer product reviews
 */

session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/admin_header.php';
require_once '../includes/ReviewManager.php';

$review_manager = new ReviewManager($conn);

// Handle moderation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve') {
            $result = $review_manager->approveReview(intval($_POST['review_id']), $_SESSION['admin_id']);
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] === 'reject') {
            $result = $review_manager->rejectReview(intval($_POST['review_id']), $_SESSION['admin_id'], $_POST['reason'] ?? '');
            $_SESSION['message'] = $result['message'];
            $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        }
        header('Location: reviews.php');
        exit;
    }
}

$pending_reviews = $review_manager->getPendingReviews();
$analytics = $review_manager->getAnalytics();

// Get all reviews for filtering
$all_reviews_stmt = $conn->query(
    "SELECT pr.*, u.name as customer_name, p.title as product_name
     FROM product_reviews pr
     JOIN users u ON pr.customer_id = u.id
     JOIN products p ON pr.product_id = p.id
     ORDER BY pr.created_at DESC"
);
$all_reviews = [];
while ($row = $all_reviews_stmt->fetch_assoc()) {
    $all_reviews[] = $row;
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

    .review-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--warning);
    }

    .review-card.approved {
        border-left-color: var(--success);
    }

    .review-card.rejected {
        border-left-color: var(--danger);
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .review-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .review-meta {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 12px;
    }

    .review-meta-item {
        display: flex;
        gap: 4px;
    }

    .rating-stars {
        color: #f59e0b;
        font-size: 1.2rem;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-approved {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-verified {
        background: #dbeafe;
        color: #1e40af;
    }

    .review-text {
        color: var(--text-primary);
        line-height: 1.6;
        margin: 16px 0;
    }

    .review-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--border-color);
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-size: 0.9rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
    }

    .empty-state-icon {
        font-size: 4rem;
        opacity: 0.5;
        margin-bottom: 16px;
    }

    .empty-state h3 {
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .empty-state p {
        color: var(--text-secondary);
    }

    .tabs {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        border-bottom: 2px solid var(--primary);
    }

    .tab-btn {
        padding: 12px 0;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-secondary);
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
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
        max-width: 400px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-family: inherit;
    }
</style>

<div class="page-header">
    <div class="container">
        <h1>⭐ Review Moderation</h1>
        <p>Moderate and manage customer product reviews</p>
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
            <div class="stat-label">Total Reviews</div>
            <div class="stat-value"><?php echo $analytics['total_reviews']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?php echo $analytics['pending_reviews']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?php echo $analytics['approved_reviews']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg Rating</div>
            <div class="stat-value"><?php echo $analytics['average_rating']; ?></div>
        </div>
    </div>

    <!-- Pending Reviews -->
    <h2 style="margin-bottom: 24px; font-size: 1.5rem; font-weight: 700; color: var(--primary);">
        🕐 Pending Reviews (<?php echo count($pending_reviews); ?>)
    </h2>

    <?php if (empty($pending_reviews)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">✅</div>
            <h3>No Pending Reviews</h3>
            <p>All reviews have been moderated!</p>
        </div>
    <?php else: ?>
        <?php foreach ($pending_reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <h3 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h3>
                        <div class="review-meta">
                            <div class="review-meta-item">
                                <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                            </div>
                            <div class="review-meta-item">
                                <span class="rating-stars"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                            </div>
                            <?php if ($review['verified_purchase']): ?>
                                <div class="review-meta-item">
                                    <span class="badge badge-verified">Verified Purchase</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="badge badge-pending">Pending</span>
                </div>

                <div style="background: #f9fafb; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                    <strong style="color: var(--text-secondary); font-size: 0.9rem;">Product:</strong>
                    <div style="color: var(--text-primary); font-weight: 500;">
                        <?php echo htmlspecialchars($review['product_name']); ?>
                    </div>
                </div>

                <div class="review-text">
                    <?php echo htmlspecialchars($review['review_text']); ?>
                </div>

                <div class="review-meta" style="font-size: 0.85rem;">
                    <span>Reviewed on <?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                </div>

                <div class="review-actions">
                    <button class="btn btn-success" onclick="approveReview(<?php echo $review['id']; ?>)">✓ Approve</button>
                    <button class="btn btn-danger" onclick="openRejectModal(<?php echo $review['id']; ?>)">✗ Reject</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- All Reviews Section -->
    <h2 style="margin: 40px 0 24px; font-size: 1.5rem; font-weight: 700; color: var(--primary);">
        📋 All Reviews
    </h2>

    <div style="overflow-x: auto; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f3f4f6; border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 16px; text-align: left; font-weight: 600;">Product</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600;">Customer</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600;">Rating</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600;">Status</th>
                    <th style="padding: 16px; text-align: left; font-weight: 600;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_reviews as $review): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 16px;"><?php echo htmlspecialchars(substr($review['product_name'], 0, 30)); ?></td>
                        <td style="padding: 16px;"><?php echo htmlspecialchars($review['customer_name']); ?></td>
                        <td style="padding: 16px;">
                            <span class="rating-stars"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                        </td>
                        <td style="padding: 16px;">
                            <span class="badge badge-<?php echo strtolower($review['status']); ?>">
                                <?php echo ucfirst($review['status']); ?>
                            </span>
                        </td>
                        <td style="padding: 16px; color: var(--text-secondary); font-size: 0.9rem;">
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-bottom: 16px; color: var(--primary);">Reject Review</h2>
        <form method="POST">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="review_id" id="rejectReviewId">

            <div class="form-group">
                <label>Reason for Rejection</label>
                <textarea name="reason" rows="4" placeholder="Why are you rejecting this review?"></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-danger">Reject</button>
                <button type="button" class="btn" style="background: #d1d5db; color: #374151;" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function approveReview(reviewId) {
        if (confirm('Approve this review?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="review_id" value="${reviewId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function openRejectModal(reviewId) {
        document.getElementById('rejectReviewId').value = reviewId;
        document.getElementById('rejectModal').classList.add('show');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('show');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('rejectModal');
        if (event.target === modal) {
            closeRejectModal();
        }
    }
</script>

<?php include '../includes/admin_footer.php'; ?>
