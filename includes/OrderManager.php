<?php
/**
 * Order Management System
 * Handles order lifecycle, status updates, timeline tracking, and notifications
 */

class OrderManager {
    private $conn;
    private $notificationManager;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    /**
     * Update order status with timeline tracking
     * @param int $order_id
     * @param string $new_status
     * @param string $notes Optional notes about the status change
     * @return array Result with success and message
     */
    public function updateOrderStatus($order_id, $new_status, $notes = '') {
        $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($new_status, $allowed_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        // Get current order details
        $order_stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $order_stmt->bind_param('i', $order_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Check if status change is valid (no going backwards)
        $status_order = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3, 'cancelled' => 4];
        if ($order['order_status'] === 'delivered' || $order['order_status'] === 'cancelled') {
            return ['success' => false, 'message' => 'Cannot update delivered or cancelled orders'];
        }

        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Update order status
            $update_stmt = $this->conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param('si', $new_status, $order_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Add timeline entry
            $timeline_stmt = $this->conn->prepare(
                "INSERT INTO order_timeline (order_id, status, notes, created_at) VALUES (?, ?, ?, NOW())"
            );
            $timeline_stmt->bind_param('iss', $order_id, $new_status, $notes);
            $timeline_stmt->execute();
            $timeline_stmt->close();

            // Send notification email
            $this->sendStatusNotificationEmail($order, $new_status);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => "Order #{$order_id} updated to " . ucfirst($new_status),
                'status' => $new_status
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()];
        }
    }

    /**
     * Get order timeline/history
     * @param int $order_id
     * @return array Timeline entries
     */
    public function getOrderTimeline($order_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM order_timeline WHERE order_id = ? ORDER BY created_at ASC"
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timeline = [];

        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
        $stmt->close();

        return $timeline;
    }

    /**
     * Bulk update order statuses
     * @param array $order_ids
     * @param string $new_status
     * @return array Result with count and message
     */
    public function bulkUpdateStatus($order_ids, $new_status) {
        if (empty($order_ids) || !is_array($order_ids)) {
            return ['success' => false, 'message' => 'No orders selected'];
        }

        $updated_count = 0;
        $failed_count = 0;

        foreach ($order_ids as $order_id) {
            $result = $this->updateOrderStatus(intval($order_id), $new_status, 'Bulk status update');
            if ($result['success']) {
                $updated_count++;
            } else {
                $failed_count++;
            }
        }

        return [
            'success' => true,
            'message' => "Updated {$updated_count} orders" . ($failed_count > 0 ? ", {$failed_count} failed" : ''),
            'updated' => $updated_count,
            'failed' => $failed_count
        ];
    }

    /**
     * Get order summary with items
     * @param int $order_id
     * @return array Order details with items
     */
    public function getOrderSummary($order_id) {
        // Get order
        $order_stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $order_stmt->bind_param('i', $order_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();

        if (!$order) {
            return null;
        }

        // Get items
        $items_stmt = $this->conn->prepare(
            "SELECT oi.*, p.title, p.cover_image FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $items_stmt->bind_param('i', $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $order['items'] = [];

        while ($item = $items_result->fetch_assoc()) {
            $order['items'][] = $item;
        }
        $items_stmt->close();

        // Get timeline
        $order['timeline'] = $this->getOrderTimeline($order_id);

        return $order;
    }

    /**
     * Send status notification email
     * @param array $order Order details
     * @param string $new_status New status
     */
    private function sendStatusNotificationEmail($order, $new_status) {
        $email = $order['customer_email'] ?? $order['email'] ?? '';
        if (empty($email)) {
            return;
        }

        $status_messages = [
            'pending' => 'Your order has been received and is awaiting processing.',
            'processing' => 'Your order is being prepared for shipment.',
            'shipped' => 'Your order has been shipped! Track your package.',
            'delivered' => 'Your order has been delivered. Thank you for shopping!',
            'cancelled' => 'Your order has been cancelled.'
        ];

        $subject = "Order #{$order['order_number']} - " . ucfirst($new_status);
        $message = $status_messages[$new_status] ?? 'Your order status has been updated.';

        // Queue email for sending
        $this->queueEmail([
            'to' => $email,
            'subject' => $subject,
            'message' => $message,
            'order_id' => $order['id'],
            'type' => 'order_status_update'
        ]);
    }

    /**
     * Queue email for background processing
     * @param array $email_data
     */
    private function queueEmail($email_data) {
        // Check if table exists, create if needed
        $check = $this->conn->query("SHOW TABLES LIKE 'email_queue'");
        if ($check->num_rows === 0) {
            $this->conn->query(
                "CREATE TABLE IF NOT EXISTS email_queue (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    to_email VARCHAR(255) NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    message LONGTEXT,
                    order_id INT,
                    type VARCHAR(50),
                    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
                    attempts INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    sent_at TIMESTAMP NULL
                )"
            );
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO email_queue (to_email, subject, message, order_id, type)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssii',
            $email_data['to'],
            $email_data['subject'],
            $email_data['message'],
            $email_data['order_id'],
            $email_data['type']
        );
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Get dashboard statistics
     * @return array Statistics
     */
    public function getDashboardStats() {
        $stats = [];

        // Orders by status
        $status_stmt = $this->conn->query(
            "SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status"
        );
        while ($row = $status_stmt->fetch_assoc()) {
            $stats['by_status'][$row['order_status']] = $row['count'];
        }

        // Pending orders
        $pending = $this->conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'");
        $stats['pending_count'] = $pending->fetch_assoc()['count'];

        // Average order value
        $avg = $this->conn->query("SELECT AVG(total_amount) as avg FROM orders WHERE order_status IN ('completed', 'delivered')");
        $stats['avg_order_value'] = $avg->fetch_assoc()['avg'] ?? 0;

        // Total revenue
        $revenue = $this->conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status IN ('completed', 'delivered')");
        $stats['total_revenue'] = $revenue->fetch_assoc()['total'] ?? 0;

        return $stats;
    }
}
?>
